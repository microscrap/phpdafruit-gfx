<?php

namespace Microscrap\GFX\PhpdaFruit\Concerns;

use InvalidArgumentException;
use RuntimeException;

/**
 * Error-diffusion dithering for monochrome / low-bit-depth panels.
 *
 * The core {@see ditherFloydSteinberg()} is pure integer/float math and needs
 * no extensions. The {@see drawImageDithered()} convenience decodes a file via
 * the GD helpers in {@see GFXImages}, so it is guarded behind an ext-gd check.
 *
 * @method int width()
 * @method int height()
 * @method static drawColorMap(int $x, int $y, array $map, int $w, int $h)
 */
trait GFXDithering
{
    /**
     * Floyd–Steinberg error diffusion over a flat, row-major grayscale map.
     *
     * Each cell is thresholded to lit/unlit and the quantisation error is
     * pushed onto the not-yet-processed neighbours with the canonical
     * 7/3/5/1-sixteenths weights. The caller's map is not mutated.
     *
     * @param  array<int, int|null>  $grayMap  Row-major grayscale values (0-255)
     * @return array<int, int> Row-major map of $on / $off values
     */
    public function ditherFloydSteinberg(array $grayMap, int $w, int $h, int $threshold = 128, int $on = 1, int $off = 0): array
    {
        if (($w < 1) || ($h < 1)) {
            throw new InvalidArgumentException("Dither dimensions must be positive, got {$w}x{$h}.");
        }

        $work = [];
        for ($i = 0, $n = $w * $h; $i < $n; $i++) {
            $work[$i] = (float) max(0, min(255, $grayMap[$i] ?? 0));
        }

        $out = [];

        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $i = ($y * $w) + $x;
                $old = $work[$i];
                $lit = $old >= $threshold;
                $new = $lit ? 255.0 : 0.0;
                $out[$i] = $lit ? $on : $off;

                $err = $old - $new;

                if (($x + 1) < $w) {
                    $work[$i + 1] += $err * 7 / 16;
                }

                if (($y + 1) < $h) {
                    if ($x > 0) {
                        $work[$i + $w - 1] += $err * 3 / 16;
                    }

                    $work[$i + $w] += $err * 5 / 16;

                    if (($x + 1) < $w) {
                        $work[$i + $w + 1] += $err * 1 / 16;
                    }
                }
            }
        }

        return $out;
    }

    /**
     * Decode an image, scale it, Floyd–Steinberg dither it, and draw it.
     *
     * Lit pixels are drawn in $color; unlit pixels are drawn as 0. Requires
     * ext-gd for the decode step (reuses {@see GFXImages}); the dither itself
     * does not.
     *
     * @throws InvalidArgumentException If the file is missing
     * @throws RuntimeException If ext-gd is absent or the image fails to load
     */
    public function drawImageDithered(string $path, int $x = 0, int $y = 0, ?int $w = null, ?int $h = null, int $color = 1, int $threshold = 128): static
    {
        if (! extension_loaded('gd')) {
            throw new RuntimeException('drawImageDithered() requires ext-gd. Install it, or dither a raw grayscale map with ditherFloydSteinberg() and draw it via drawColorMap().');
        }

        if (! file_exists($path)) {
            throw new InvalidArgumentException("Image file not found: {$path}");
        }

        $image = $this->loadImage($path);
        if (! $image) {
            throw new RuntimeException("Failed to load image: {$path}");
        }

        $w = $w ?? $this->width();
        $h = $h ?? $this->height();

        if (($x >= $this->width()) || ($y >= $this->height()) ||
            (($x + $w) <= 0) || (($y + $h) <= 0)) {
            imagedestroy($image);

            return $this;
        }

        $resized = $this->resizeImage($image, $w, $h);
        imagedestroy($image);

        $grayMap = $this->imageToColorMap($resized, $w, $h);
        imagedestroy($resized);

        $dithered = $this->ditherFloydSteinberg($grayMap, $w, $h, $threshold, $color, 0);

        return $this->drawColorMap($x, $y, $dithered, $w, $h);
    }
}
