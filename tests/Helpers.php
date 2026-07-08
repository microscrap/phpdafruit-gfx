<?php

use BareMetal\Contracts\Framebuffers\DTO\FormatSpec;
use BareMetal\Contracts\Framebuffers\Enums\BitDepth;
use BareMetal\Contracts\Framebuffers\Enums\PixelFormat;
use BareMetal\Framebuffers\FullFramebuffer;
use Microscrap\GFX\PhpdaFruit\PhpdafruitGFX;

/**
 * A renderer backed by a ROW_MAJOR / 8-bit canvas, so every pixel maps to one
 * byte in the dump and can be asserted directly.
 */
function gfxRenderer(int $width, int $height): PhpdafruitGFX
{
    return new PhpdafruitGFX(
        new FullFramebuffer($width, $height, new FormatSpec(PixelFormat::ROW_MAJOR, BitDepth::B8))
    );
}

/**
 * The canvas as a flat row-major list of pixel values.
 *
 * @return array<int, int>
 */
function gfxPixels(PhpdafruitGFX $renderer): array
{
    return $renderer->buffer()->dump()[0]->raw_data;
}

/**
 * One pixel value, addressed in physical (buffer) coordinates.
 */
function gfxPixel(PhpdafruitGFX $renderer, int $x, int $y): int
{
    return gfxPixels($renderer)[($y * $renderer->buffer()->viewportWidth()) + $x];
}

/**
 * Count of non-zero (painted) pixels on the canvas.
 */
function gfxPaintedCount(PhpdafruitGFX $renderer): int
{
    return count(array_filter(gfxPixels($renderer), fn (int $value) => $value !== 0));
}
