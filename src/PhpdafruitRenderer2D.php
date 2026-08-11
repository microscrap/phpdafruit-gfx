<?php

namespace Microscrap\GFX\PhpdaFruit;

use RuntimeException;
use ScrapyardIO\Tubes\Contracts\Framebuffers\DumpedBuffer;
use ScrapyardIO\Tubes\Contracts\Framebuffers\Enums\PixelFormat;
use ScrapyardIO\Tubes\Contracts\Framebuffers\FormatSpec;
use ScrapyardIO\Tubes\Contracts\Framebuffers\Framebuffer;
use ScrapyardIO\Tubes\Contracts\Framebuffers\ManagedFramebuffer;
use ScrapyardIO\Tubes\Contracts\Rendering\RenderingException;
use ScrapyardIO\Tubes\Framebuffers\DirtyRegionsBuffer;
use ScrapyardIO\Tubes\Framebuffers\FullFramebuffer;
use ScrapyardIO\Tubes\Framebuffers\PageSegmentBuffer;
use ScrapyardIO\Tubes\Rendering\Renderer2D;
use Microscrap\GFX\PhpdaFruit\Concerns\GFXAPI;

/**
 * Software AdafruitGFX-style renderer: every primitive resolves to logical
 * pixels written into a Managed framebuffer, which packs them into the
 * display's declared byte layout on flush/render().
 *
 * @property-read int $height
 * @property-read int $width
 * @property int $rotation
 */
class PhpdafruitRenderer2D extends Renderer2D
{
    use GFXAPI;

    public function __construct(?Framebuffer $buffer = null)
    {
        if (! is_null($buffer)) {
            $this->setFramebuffer($buffer);
        }
    }

    public function setFramebuffer(Framebuffer &$framebuffer): static
    {
        if (! $this->supportsFramebuffer($framebuffer)) {
            throw new RenderingException(
                'PhpdafruitRenderer2D requires a Managed framebuffer; '.$framebuffer::class.' given.'
            );
        }

        return parent::setFramebuffer($framebuffer);
    }

    /**
     * @deprecated Use {@see setFramebuffer()} — kept for 0.6 call sites.
     */
    public function useFramebuffer(Framebuffer $framebuffer): static
    {
        return $this->setFramebuffer($framebuffer);
    }

    public function supportsFramebuffer(Framebuffer $framebuffer): bool
    {
        return $framebuffer instanceof ManagedFramebuffer;
    }

    public function drawPixel(int $x, int $y, int $color): static
    {
        if (($x < 0) || ($y < 0) || ($x >= $this->width()) || ($y >= $this->height())) {
            return $this;
        }

        if (! $this->clipAllows($x, $y)) {
            return $this;
        }

        [$x, $y] = $this->applyRotation($x, $y);
        $this->framebuffer()->setPixel($x, $y, $color);

        return $this;
    }

    public function drawSegment(int $x, int $y, int $width, int $height, int $color): static
    {
        if (($x >= $this->width()) || ($y >= $this->height()) ||
            ($x + $width <= 0) || ($y + $height <= 0) ||
            ($width <= 0) || ($height <= 0)) {
            return $this;
        }

        // Clip in logical space, before rotation, so a rejected fill never
        // reaches the buffer and never marks a region dirty.
        $segment = $this->clipSegment($x, $y, $width, $height);

        if (is_null($segment)) {
            return $this;
        }

        [$x, $y, $width, $height] = [$segment->x, $segment->y, $segment->width, $segment->height];

        $buffer = $this->framebuffer();

        if ($this->rotation === 0) {
            $left = max(0, $x);
            $top = max(0, $y);
            $right = min($x + $width, $buffer->viewportWidth());
            $bottom = min($y + $height, $buffer->viewportHeight());

            $clipped_width = $right - $left;
            $clipped_height = $bottom - $top;

            if ($clipped_width > 0 && $clipped_height > 0) {
                $buffer->setSegment($left, $top, $clipped_width, $clipped_height, $color);
            }

            return $this;
        }

        $corners = [
            [$x, $y],
            [$x + $width - 1, $y],
            [$x, $y + $height - 1],
            [$x + $width - 1, $y + $height - 1],
        ];

        $rotated_corners = [];
        foreach ($corners as [$cx, $cy]) {
            $rotated_corners[] = $this->applyRotation($cx, $cy);
        }

        [$min_x, $min_y, $max_x, $max_y] = $this->getBoundingBox($rotated_corners);

        $clipped_min_x = max(0, (int) $min_x);
        $clipped_min_y = max(0, (int) $min_y);
        $clipped_max_x = min($buffer->viewportWidth() - 1, (int) $max_x);
        $clipped_max_y = min($buffer->viewportHeight() - 1, (int) $max_y);

        if ($clipped_max_x < $clipped_min_x || $clipped_max_y < $clipped_min_y) {
            return $this;
        }

        $fill_width = $clipped_max_x - $clipped_min_x + 1;
        $fill_height = $clipped_max_y - $clipped_min_y + 1;

        $buffer->setSegment($clipped_min_x, $clipped_min_y, $fill_width, $fill_height, $color);

        return $this;
    }

    /**
     * @param  array<int, array{0: int, 1: int, 2: int}>  $pixels
     */
    public function drawPixels(array $pixels): static
    {
        foreach ($pixels as [$x, $y, $color]) {
            $this->drawPixel($x, $y, $color);
        }

        return $this;
    }

    /**
     * @param  array<int, array{0: int, 1: int, 2: int, 3: int, 4: int}>  $lines
     */
    public function drawLines(array $lines): static
    {
        foreach ($lines as [$x0, $y0, $x1, $y1, $color]) {
            $this->drawLine($x0, $y0, $x1, $y1, $color);
        }

        return $this;
    }

    public function fill(int $color): static
    {
        $clip = $this->clip();

        // A full-buffer fill ignores the clip, so a clipped fill has to go
        // through the rect path and cover just the clip region.
        if (! is_null($clip)) {
            return $this->fillRect($clip->x, $clip->y, $clip->width, $clip->height, $color);
        }

        return $this->fillScreen($color);
    }

    /**
     * Bound framebuffer (legacy alias for {@see framebuffer()}).
     */
    public function buffer(): Framebuffer
    {
        return $this->framebuffer();
    }

    /**
     * @return array<int, DumpedBuffer>
     */
    public function render(): array
    {
        $framebuffer = $this->framebuffer();
        $frames = $framebuffer->flush($framebuffer->hostFormat(), true);

        return is_array($frames) ? $frames : [];
    }

    public static function preferredFramebuffer(FormatSpec $format_spec, int $width, int $height): Framebuffer
    {
        return match ($format_spec->pixel_format) {
            PixelFormat::MONO_VERTICAL_PAGE => PageSegmentBuffer::sized($width, $height, $format_spec),
            PixelFormat::ROW_MAJOR => DirtyRegionsBuffer::sized($width, $height, $format_spec),
            default => FullFramebuffer::sized($width, $height, $format_spec),
        };
    }

    public function __get(string $name): mixed
    {
        return match ($name) {
            'rotation' => $this->getRotation(),
            'height' => $this->height(),
            'width' => $this->width(),
            default => throw new RuntimeException("Unknown property $name"),
        };
    }

    public function __set(string $name, mixed $value): void
    {
        match ($name) {
            'rotation' => $this->setRotation((int) $value),
            default => throw new RuntimeException("Unknown property $name"),
        };
    }
}
