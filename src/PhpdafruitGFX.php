<?php

namespace Microscrap\GFX\PhpdaFruit;

use BareMetal\Contracts\Framebuffers\DTO\DumpedBuffer;
use BareMetal\Contracts\Framebuffers\DTO\FormatSpec;
use BareMetal\Contracts\Framebuffers\Enums\PixelFormat;
use BareMetal\Contracts\Framebuffers\Framebuffer;
use BareMetal\Framebuffers\DirtyRegionsBuffer;
use BareMetal\Framebuffers\FormatSpecFramebuffer;
use BareMetal\Framebuffers\FullFramebuffer;
use BareMetal\Framebuffers\PageSegmentBuffer;
use BareMetal\GFX\Renderer2D;
use Microscrap\GFX\PhpdaFruit\Concerns\GFXAPI;
use RuntimeException;

/**
 * Software AdafruitGFX-style renderer: every primitive resolves to logical
 * pixels written into a FormatSpecFramebuffer, which packs them into the
 * display's declared byte layout on render().
 *
 * @property-read int $height
 * @property-read int $width
 * @property int $rotation
 */
class PhpdafruitGFX extends Renderer2D
{
    use GFXAPI;

    public function __construct(
        protected FormatSpecFramebuffer $buffer,
    ) {}

    public function drawPixel(int $x, int $y, int $color): static
    {
        // Bounds check in logical coordinates
        if (($x < 0) || ($y < 0) || ($x >= $this->width) || ($y >= $this->height)) {
            return $this;
        }

        [$x, $y] = $this->applyRotation($x, $y);
        $this->buffer->setPixel($x, $y, $color);

        return $this;
    }

    public function drawSegment(int $x, int $y, int $width, int $height, int $color): static
    {
        // Early bounds check - if the entire segment is out of bounds, skip
        if (($x >= $this->width) || ($y >= $this->height) ||
            ($x + $width <= 0) || ($y + $height <= 0) ||
            ($width <= 0) || ($height <= 0)) {
            return $this;
        }

        // Fast path: no rotation - use buffer->setSegment() directly
        if ($this->rotation === 0) {
            // Calculate actual on-screen region (handles negative coordinates correctly)
            $left = max(0, $x);
            $top = max(0, $y);
            $right = min($x + $width, $this->buffer->viewportWidth());
            $bottom = min($y + $height, $this->buffer->viewportHeight());

            $clipped_width = $right - $left;
            $clipped_height = $bottom - $top;

            // Only draw if there's actual area to draw
            if ($clipped_width > 0 && $clipped_height > 0) {
                $this->buffer->setSegment($left, $top, $clipped_width, $clipped_height, $color);
            }

            return $this;
        }

        // Rotated segment: Since we only support 90° rotations, a rotated rectangle
        // is still a rectangle. Calculate the rotated bounding box and use setSegment().
        // This is O(4 corners + 1 segment) instead of O(width × height pixels).

        $corners = [
            [$x, $y],                          // Top-left
            [$x + $width - 1, $y],             // Top-right
            [$x, $y + $height - 1],            // Bottom-left
            [$x + $width - 1, $y + $height - 1], // Bottom-right
        ];

        $rotated_corners = [];
        foreach ($corners as [$cx, $cy]) {
            $rotated_corners[] = $this->applyRotation($cx, $cy);
        }

        // Find bounding box of rotated rectangle
        [$min_x, $min_y, $max_x, $max_y] = $this->getBoundingBox($rotated_corners);

        // Clip to actual buffer dimensions
        $clipped_min_x = max(0, (int) $min_x);
        $clipped_min_y = max(0, (int) $min_y);
        $clipped_max_x = min($this->buffer->viewportWidth() - 1, (int) $max_x);
        $clipped_max_y = min($this->buffer->viewportHeight() - 1, (int) $max_y);

        // If the rotated segment is completely out of bounds, nothing to draw
        if ($clipped_max_x < $clipped_min_x || $clipped_max_y < $clipped_min_y) {
            return $this;
        }

        // Calculate dimensions of the clipped rotated bounding box
        $fill_width = $clipped_max_x - $clipped_min_x + 1;
        $fill_height = $clipped_max_y - $clipped_min_y + 1;

        // Use buffer->setSegment() directly on the rotated bounding box
        // This is efficient because 90° rotations preserve rectangularity
        $this->buffer->setSegment($clipped_min_x, $clipped_min_y, $fill_width, $fill_height, $color);

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

    public function drawHLine(int $x, int $y, int $w, int $color): static
    {
        return $this->drawHorizontalLine($x, $y, $w, $color);
    }

    public function drawVLine(int $x, int $y, int $h, int $color): static
    {
        return $this->drawVerticalLine($x, $y, $h, $color);
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
        return $this->fillScreen($color);
    }

    public function buffer(): FormatSpecFramebuffer
    {
        return $this->buffer;
    }

    /**
     * @return array<int, DumpedBuffer>
     */
    public function render(): array
    {
        return $this->buffer()->dump();
    }

    public static function preferredFramebuffer(FormatSpec $format_spec, int $width, int $height): Framebuffer
    {
        return match ($format_spec->pixel_format) {
            PixelFormat::MONO_VERTICAL_PAGE => new PageSegmentBuffer($width, $height, $format_spec),
            PixelFormat::ROW_MAJOR => new DirtyRegionsBuffer($width, $height, $format_spec),
            default => new FullFramebuffer($width, $height, $format_spec),
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
