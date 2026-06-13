<?php

namespace Microscrap\GFX\PhpdaFruit\Concerns;

trait GFXRects
{
    public function drawRect(int $x, int $y, int $w, int $h, int $color): static
    {
        $this->drawHorizontalLine($x, $y, $w, $color);
        $this->drawHorizontalLine($x, ($y + $h) - 1, $w, $color);
        $this->drawVerticalLine($x, $y, $h, $color);

        return $this->drawVerticalLine(($x + $w) - 1, $y, $h, $color);
    }

    public function drawRoundRect(int $x, int $y, int $w, int $h, int $r, int $color): static
    {
        $max_radius = (($w < $h) ? $w : $h) / 2;
        if ($r > $max_radius) {
            $r = $max_radius;
        }

        $this->drawHorizontalLine($x + $r, $y, $w - 2 * $r, $color);
        $this->drawHorizontalLine($x + $r, ($y + $h) - 1, $w - 2 * $r, $color);
        $this->drawVerticalLine($x, $y + $r, $h - 2 * $r, $color);
        $this->drawVerticalLine($x + $w - 1, $y + $r, $h - 2 * $r, $color);

        $this->drawCircleHelper($x + $r, $y + $r, $r, 1, $color);
        $this->drawCircleHelper($x + $w - $r - 1, $y + $r, $r, 2, $color);
        $this->drawCircleHelper($x + $w - $r - 1, $y + $h - $r - 1, $r, 4, $color);
        $this->drawCircleHelper($x + $r, $y + $h - $r - 1, $r, 8, $color);

        return $this;
    }

    public function fillRect(int $x, int $y, int $w, int $h, int $color): static
    {
        // Use drawSegment() for massive performance boost
        // One segment call instead of w vertical line calls
        return $this->drawSegment($x, $y, $w, $h, $color);
    }

    public function fillRoundRect(int $x, int $y, int $w, int $h, int $r, int $color): static
    {
        $max_radius = (($w < $h) ? $w : $h) / 2;
        if ($r > $max_radius) {
            $r = $max_radius;
        }

        // Fill center rectangle
        $this->fillRect($x + $r, $y, $w - 2 * $r, $h, $color);

        // Fill both side rounded areas
        $this->fillCircleHelper($x + $w - $r - 1, $y + $r, $r, 1, $h - 2 * $r - 1, $color);
        $this->fillCircleHelper($x + $r, $y + $r, $r, 2, $h - 2 * $r - 1, $color);

        return $this;
    }

    public function fillScreen(int $color): static
    {
        return $this->fillRect(0, 0, $this->width(), $this->height(), $color);
    }

    /**
     * Alias for fillRect() for API compatibility
     *
     * @deprecated Use fillRect() instead
     */
    public function drawFillRect(int $x, int $y, int $w, int $h, int $color): static
    {
        return $this->fillRect($x, $y, $w, $h, $color);
    }
}
