<?php

use Fabricate\Framebuffers\FormatSpec;
use Fabricate\Contracts\Framebuffers\Enums\BitDepth;
use Fabricate\Contracts\Framebuffers\Enums\PixelFormat;
use Fabricate\Framebuffers\Strategy\FullFramebuffer;
use Microscrap\GFX\PhpdaFruit\PhpdafruitGfx;

/**
 * A renderer backed by a ROW_MAJOR / 8-bit canvas, so every pixel maps to one
 * byte in the dump and can be asserted directly.
 */
function gfxRenderer(int $width, int $height): PhpdafruitGfx
{
    return new PhpdafruitGfx(
        new FullFramebuffer($width, $height, new FormatSpec(PixelFormat::ROW_MAJOR, BitDepth::B8))
    );
}

/**
 * The canvas as a flat row-major list of pixel values.
 *
 * @return array<int, int>
 */
function gfxPixels(PhpdafruitGfx $renderer): array
{
    return $renderer->buffer()->dump()[0]->raw_data;
}

/**
 * One pixel value, addressed in physical (buffer) coordinates.
 */
function gfxPixel(PhpdafruitGfx $renderer, int $x, int $y): int
{
    return gfxPixels($renderer)[($y * $renderer->buffer()->viewportWidth()) + $x];
}

/**
 * Count of non-zero (painted) pixels on the canvas.
 */
function gfxPaintedCount(PhpdafruitGfx $renderer): int
{
    return count(array_filter(gfxPixels($renderer), fn (int $value) => $value !== 0));
}
