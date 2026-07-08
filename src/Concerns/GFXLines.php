<?php

namespace Microscrap\GFX\PhpdaFruit\Concerns;

trait GFXLines
{
    public function drawLine(int $x0, int $y0, int $x1, int $y1, int $color): static
    {
        if ($x0 == $x1) {
            if ($y0 > $y1) {
                [$y0, $y1] = [$y1, $y0];
            }

            return $this->drawVerticalLine($x0, $y0, ($y1 - $y0) + 1, $color);
        } elseif ($y0 == $y1) {
            if ($x0 > $x1) {
                [$x0, $x1] = [$x1, $x0];
            }

            return $this->drawHorizontalLine($x0, $y0, ($x1 - $x0) + 1, $color);

        }

        return $this->drawArbitraryLine($x0, $y0, $x1, $y1, $color);
    }

    public function drawArbitraryLine(int $x0, int $y0, int $x1, int $y1, int $color): static
    {
        // Cohen-Sutherland line clipping to viewport bounds
        // This prevents calling drawPixel() for off-screen portions
        $clipped = $this->clipLine($x0, $y0, $x1, $y1);

        if (is_null($clipped)) {
            // Line is completely outside viewport
            return $this;
        }

        [$x0, $y0, $x1, $y1] = $clipped;

        // Bresenham's line algorithm
        $steep = abs($y1 - $y0) > abs($x1 - $x0);
        if ($steep) {
            [$x0, $y0] = [$y0, $x0];
            [$x1, $y1] = [$y1, $x1];
        }

        if ($x0 > $x1) {
            [$x0, $x1] = [$x1, $x0];
            [$y0, $y1] = [$y1, $y0];
        }

        $dx = $x1 - $x0;
        $dy = abs($y1 - $y0);

        $err = $dx / 2;
        if ($y0 < $y1) {
            $y_step = 1;
        } else {
            $y_step = -1;
        }

        while ($x0 <= $x1) {
            if ($steep) {
                $this->drawPixel($y0, $x0, $color);
            } else {
                $this->drawPixel($x0, $y0, $color);
            }
            $err -= $dy;
            if ($err < 0) {
                $y0 += $y_step;
                $err += $dx;
            }

            $x0++;
        }

        return $this;
    }

    /**
     * Cohen-Sutherland line clipping algorithm
     * Clips line to viewport bounds [0, width) x [0, height)
     *
     * @return array|null Returns [x0, y0, x1, y1] if line is visible, null if completely clipped
     */
    protected function clipLine(int $x0, int $y0, int $x1, int $y1): ?array
    {
        $xMin = 0;
        $yMin = 0;
        $xMax = $this->width() - 1;
        $yMax = $this->height() - 1;

        // Compute outcodes for both endpoints
        $outcode0 = $this->computeOutcode($x0, $y0, $xMin, $yMin, $xMax, $yMax);
        $outcode1 = $this->computeOutcode($x1, $y1, $xMin, $yMin, $xMax, $yMax);

        while (true) {
            if (($outcode0 | $outcode1) === 0) {
                // Both points inside viewport - accept
                return [$x0, $y0, $x1, $y1];
            }

            if (($outcode0 & $outcode1) !== 0) {
                // Both points share an outside zone - reject
                return null;
            }

            // At least one point is outside - clip it
            $outcodeOut = $outcode0 !== 0 ? $outcode0 : $outcode1;

            // Find intersection point
            if (($outcodeOut & 8) !== 0) { // Top
                $x = $x0 + ($x1 - $x0) * ($yMax - $y0) / ($y1 - $y0);
                $y = $yMax;
            } elseif (($outcodeOut & 4) !== 0) { // Bottom
                $x = $x0 + ($x1 - $x0) * ($yMin - $y0) / ($y1 - $y0);
                $y = $yMin;
            } elseif (($outcodeOut & 2) !== 0) { // Right
                $y = $y0 + ($y1 - $y0) * ($xMax - $x0) / ($x1 - $x0);
                $x = $xMax;
            } else { // Left (outcodeOut & 1)
                $y = $y0 + ($y1 - $y0) * ($xMin - $x0) / ($x1 - $x0);
                $x = $xMin;
            }

            // Update point and outcode
            if ($outcodeOut === $outcode0) {
                $x0 = (int) $x;
                $y0 = (int) $y;
                $outcode0 = $this->computeOutcode($x0, $y0, $xMin, $yMin, $xMax, $yMax);
            } else {
                $x1 = (int) $x;
                $y1 = (int) $y;
                $outcode1 = $this->computeOutcode($x1, $y1, $xMin, $yMin, $xMax, $yMax);
            }
        }
    }

    /**
     * Compute outcode for Cohen-Sutherland algorithm
     * Bits: LEFT=1, RIGHT=2, BOTTOM=4, TOP=8
     */
    protected function computeOutcode(int $x, int $y, int $xMin, int $yMin, int $xMax, int $yMax): int
    {
        $code = 0;
        if ($x < $xMin) {
            $code |= 1;
        }      // LEFT
        if ($x > $xMax) {
            $code |= 2;
        }      // RIGHT
        if ($y < $yMin) {
            $code |= 4;
        }      // BOTTOM
        if ($y > $yMax) {
            $code |= 8;
        }      // TOP

        return $code;
    }

    /**
     * Draw a horizontal line with rotation applied
     *
     * NOTE: Rotation logic is duplicated here instead of using applyRotation()
     * because we optimize by calling the appropriate *Internal method:
     * - 0°/180°: stays horizontal → drawHorizontalLineInternal()
     * - 90°/270°: becomes vertical → drawVerticalLineInternal()
     * This allows using buffer->segment() efficiently instead of pixel-by-pixel.
     *
     * Trade-off: Faster execution vs. duplicated rotation math
     */
    public function drawHorizontalLine(int $x, int $y, int $w, int $color): static
    {
        if ($w < 0) {
            $w *= -1;
            $x -= $w - 1;
            if ($x < 0) {
                $w += $x;
                $x = 0;
            }
        }

        // Bounds check in logical coordinates
        if (($y < 0) || ($y >= $this->height()) || ($x >= $this->width()) || (($x + $w - 1) < 0)) {
            return $this;
        }

        // Clip left
        if ($x < 0) {
            $w += $x;
            $x = 0;
        }

        // Clip right
        if (($x + $w) > $this->width()) {
            $w = $this->width() - $x;
        }

        switch ($this->getRotation()) {
            case 0:
                $this->drawHorizontalLineInternal($x, $y, $w, $color);
                break;
            case 1:
                $t = $x;
                $x = $this->width() - 1 - $y;
                $y = $t;
                $this->drawVerticalLineInternal($x, $y, $w, $color);
                break;
            case 2:
                $x = $this->width() - 1 - $x;
                $y = $this->height() - 1 - $y;
                $x -= $w - 1;
                $this->drawHorizontalLineInternal($x, $y, $w, $color);
                break;
            case 3:
                $t = $x;
                $x = $y;
                $y = $this->height() - 1 - $t;
                $y -= $w - 1;
                $this->drawVerticalLineInternal($x, $y, $w, $color);
                break;
        }

        return $this;
    }

    /**
     * Draw a vertical line with rotation applied
     *
     * NOTE: Rotation logic is duplicated here (see drawHorizontalLine comment)
     */
    public function drawVerticalLine(int $x, int $y, int $h, int $color): static
    {
        if ($h < 0) {
            $h *= -1;
            $y -= $h - 1;
            if ($y < 0) {
                $h += $y;
                $y = 0;
            }
        }

        // Bounds check in logical coordinates
        if (($x < 0) || ($x >= $this->width()) || ($y >= $this->height()) || (($y + $h - 1) < 0)) {
            return $this;
        }

        // Clip top
        if ($y < 0) {
            $h += $y;
            $y = 0;
        }

        // Clip bottom
        if (($y + $h) > $this->height()) {
            $h = $this->height() - $y;
        }

        switch ($this->getRotation()) {
            case 0:
                $this->drawVerticalLineInternal($x, $y, $h, $color);
                break;
            case 1:
                $t = $x;
                $x = $this->width() - 1 - $y;
                $y = $t;
                $x -= $h - 1;
                $this->drawHorizontalLineInternal($x, $y, $h, $color);
                break;
            case 2:
                $x = $this->width() - 1 - $x;
                $y = $this->height() - 1 - $y;
                $y -= $h - 1;
                $this->drawVerticalLineInternal($x, $y, $h, $color);
                break;
            case 3:
                $t = $x;
                $x = $y;
                $y = $this->height() - 1 - $t;
                $this->drawHorizontalLineInternal($x, $y, $h, $color);
                break;
        }

        return $this;
    }

    /**
     * Draw horizontal line in raw coordinates (no rotation)
     */
    protected function drawHorizontalLineInternal(int $x, int $y, int $w, int $color): void
    {
        if (($y >= 0) && ($y < $this->buffer()->viewportHeight())) {
            if ($x < 0) {
                $w += $x;
                $x = 0;
            }

            if (($x + $w) > $this->buffer()->viewportWidth()) {
                $w = ($this->buffer()->viewportWidth() - $x);
            }

            if ($w > 0) {
                // Use buffer->segment() for efficiency instead of pixel-by-pixel
                $this->buffer()->setSegment($x, $y, $w, 1, $color);
            }
        }
    }

    /**
     * Draw vertical line in raw coordinates (no rotation)
     */
    protected function drawVerticalLineInternal(int $x, int $y, int $h, int $color): void
    {
        if (($x >= 0) && ($x < $this->buffer()->viewportWidth())) {
            if ($y < 0) {
                $h += $y;
                $y = 0;
            }

            if (($y + $h) > $this->buffer()->viewportHeight()) {
                $h = ($this->buffer()->viewportHeight() - $y);
            }

            if ($h > 0) {
                // Use buffer->segment() for efficiency instead of pixel-by-pixel
                $this->buffer()->setSegment($x, $y, 1, $h, $color);
            }
        }
    }
}
