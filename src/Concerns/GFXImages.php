<?php

namespace Microscrap\GFX\PhpdaFruit\Concerns;

use GdImage;
use InvalidArgumentException;
use RuntimeException;

trait GFXImages
{
    /**
     * Load and draw an image from file
     *
     * @param  string  $path  Path to image file
     * @param  int  $x  X coordinate
     * @param  int  $y  Y coordinate
     * @param  int|null  $w  Target width (defaults to display width)
     * @param  int|null  $h  Target height (defaults to display height)
     *
     * @throws InvalidArgumentException If file not found
     * @throws RuntimeException If image loading fails
     */
    public function drawImageFromFile(string $path, int $x = 0, int $y = 0, ?int $w = null, ?int $h = null): static
    {
        if (! extension_loaded('gd')) {
            throw new RuntimeException('drawImageFromFile() requires ext-gd. Install it, or feed raw pixels to drawColorMap()/drawBitmap() instead.');
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

        // Early exit if image would be completely off-screen
        if (($x >= $this->width()) || ($y >= $this->height()) ||
            (($x + $w) <= 0) || (($y + $h) <= 0)) {
            imagedestroy($image);

            return $this;
        }

        $resized = $this->resizeImage($image, $w, $h);
        imagedestroy($image);

        $colorMap = $this->imageToColorMap($resized, $w, $h);
        imagedestroy($resized);

        return $this->drawColorMap($x, $y, $colorMap, $w, $h);
    }

    /**
     * Load image from file using appropriate GD loader
     *
     * @param  string  $path  Path to image file
     * @return GdImage|false GD image resource or false on failure
     */
    protected function loadImage(string $path): GdImage|false
    {
        return match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'jpg', 'jpeg' => imagecreatefromjpeg($path),
            'png' => imagecreatefrompng($path),
            'gif' => imagecreatefromgif($path),
            'webp' => imagecreatefromwebp($path),
            'bmp' => imagecreatefrombmp($path),
            default => false,
        };
    }

    /**
     * Resize image using high-quality resampling
     *
     * Note: This stretches the image to fit target dimensions.
     * Aspect ratio is NOT preserved.
     *
     * @param  GdImage  $image  Source image
     * @param  int  $w  Target width
     * @param  int  $h  Target height
     * @return GdImage Resized image
     */
    protected function resizeImage(GdImage $image, int $w, int $h): GdImage
    {
        $origWidth = imagesx($image);
        $origHeight = imagesy($image);

        $resized = imagecreatetruecolor($w, $h);
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $w, $h, $origWidth, $origHeight);

        return $resized;
    }

    /**
     * Convert GD image to color map (array of grayscale int values)
     *
     * Extracts RGB from each pixel and converts to grayscale using the
     * standard luminosity formula weighted for human eye sensitivity:
     * - Red: 29.9% (weight 77/256)
     * - Green: 58.7% (weight 150/256) - most sensitive
     * - Blue: 11.4% (weight 29/256)
     *
     * Note: Alpha channel is currently ignored (transparency lost).
     *
     * TODO: Consider adding full-color support for RGB displays by
     * preserving RGB values instead of converting to grayscale.
     *
     * @param  GdImage  $image  Source image
     * @param  int  $w  Width
     * @param  int  $h  Height
     * @return array Flat array of grayscale values (0-255)
     */
    protected function imageToColorMap(GdImage $image, int $w, int $h): array
    {
        $colorMap = [];
        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                // Get 32-bit RGBA color from GD image
                $rgb = imagecolorat($image, $x, $y);

                // Extract RGB components (GD format: 0xAARRGGBB)
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;

                // Convert RGB to grayscale using luminosity formula
                // Human eye is most sensitive to green, then red, then blue
                // Standard weights: R=0.299, G=0.587, B=0.114
                // Using integer math: (77*R + 150*G + 29*B) / 256
                $gray = (int) ((77 * $r + 150 * $g + 29 * $b) / 256);

                $colorMap[] = $gray;
            }
        }

        return $colorMap;
    }
}
