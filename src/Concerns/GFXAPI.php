<?php

namespace Microscrap\GFX\PhpdaFruit\Concerns;

use InvalidArgumentException;
use OutOfBoundsException;
use BareMetal\Framebuffers\FormatSpecFramebuffer;

/**
 * @property FormatSpecFramebuffer $buffer
 *
 * @method FormatSpecFramebuffer buffer()
 */
trait GFXAPI
{
    use GFXBitmaps, GFXDithering, GFXImages, GFXText;
    use GFXLines, GFXRects, GFXRounds, GFXTriangles;

    protected int $rotation = 0;

    public function getRotation(): int
    {
        return $this->rotation;
    }

    public function setRotation(int $rotation): void
    {
        if (($rotation >= 0) && ($rotation < 4)) {
            $this->rotation = $rotation;
        } else {
            throw new OutOfBoundsException("0 - 3 are the only valid rotation values. Not {$rotation}");
        }
    }

    protected function applyRotation(int $x, int $y): array
    {
        switch ($this->rotation) {
            case 1: // 90° rotation
                $t = $x;
                $x = $this->buffer->viewportWidth() - 1 - $y;
                $y = $t;
                break;
            case 2: // 180° rotation
                $x = $this->buffer->viewportWidth() - 1 - $x;
                $y = $this->buffer->viewportHeight() - 1 - $y;
                break;
            case 3: // 270° rotation
                $t = $x;
                $x = $y;
                $y = $this->buffer->viewportHeight() - 1 - $t;
                break;
        }

        return [$x, $y];
    }

    public function width(): int
    {
        return ($this->rotation & 1)
            ? $this->buffer->viewportHeight()
            : $this->buffer->viewportWidth();
    }

    public function height(): int
    {
        return ($this->rotation & 1)
            ? $this->buffer->viewportWidth()
            : $this->buffer->viewportHeight();
    }

    /**
     * Calculate bounding box from array of coordinates
     *
     * @throws InvalidArgumentException if points array is empty
     */
    protected function getBoundingBox(array $points): array
    {
        if (empty($points)) {
            throw new InvalidArgumentException('Cannot compute bounding box of empty point set');
        }

        $min_x = PHP_INT_MAX;
        $min_y = PHP_INT_MAX;
        $max_x = PHP_INT_MIN;
        $max_y = PHP_INT_MIN;

        foreach ($points as [$x, $y]) {
            $min_x = min($min_x, $x);
            $min_y = min($min_y, $y);
            $max_x = max($max_x, $x);
            $max_y = max($max_y, $y);
        }

        return [$min_x, $min_y, $max_x, $max_y];
    }
}
