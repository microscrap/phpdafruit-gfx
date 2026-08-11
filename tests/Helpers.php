<?php

use ScrapyardIO\Tubes\Contracts\Framebuffers\Enums\BitDepth;
use ScrapyardIO\Tubes\Contracts\Framebuffers\Enums\PixelFormat;
use ScrapyardIO\Tubes\Contracts\Framebuffers\FormatSpec;
use ScrapyardIO\Tubes\Framebuffers\FullFramebuffer;
use Microscrap\GFX\PhpdaFruit\PhpdafruitRenderer2D;

/**
 * A renderer backed by a ROW_MAJOR / 8-bit canvas, so every pixel maps to one
 * byte in the dump and can be asserted directly.
 */
function gfxRenderer(int $width, int $height): PhpdafruitRenderer2D
{
    $buffer = FullFramebuffer::sized(
        $width,
        $height,
        new FormatSpec(PixelFormat::ROW_MAJOR, BitDepth::B8),
    );

    return new PhpdafruitRenderer2D($buffer);
}

/**
 * The canvas as a flat row-major list of pixel values.
 *
 * @return array<int, int>
 */
function gfxPixels(PhpdafruitRenderer2D $renderer): array
{
    $bytes = $renderer->framebuffer()->dump();

    return array_values(unpack('C*', $bytes) ?: []);
}

/**
 * One pixel value, addressed in physical (buffer) coordinates.
 */
function gfxPixel(PhpdafruitRenderer2D $renderer, int $x, int $y): int
{
    return $renderer->framebuffer()->getPixel($x, $y);
}

/**
 * Count of non-zero (painted) pixels on the canvas.
 */
function gfxPaintedCount(PhpdafruitRenderer2D $renderer): int
{
    $width = $renderer->framebuffer()->viewportWidth();
    $height = $renderer->framebuffer()->viewportHeight();
    $count = 0;

    for ($y = 0; $y < $height; $y++) {
        for ($x = 0; $x < $width; $x++) {
            if ($renderer->framebuffer()->getPixel($x, $y) !== 0) {
                $count++;
            }
        }
    }

    return $count;
}
