<?php

use Microscrap\GFX\PhpdaFruit\GFXRenderer;
use ScrapyardIO\NutsAndBolts\Buffers\FullFrameBuffer;
use ScrapyardIO\NutsAndBolts\DataObjects\FormatSpec;
use ScrapyardIO\NutsAndBolts\Enums\BitDepth;
use ScrapyardIO\NutsAndBolts\Enums\PixelFormat;

/**
 * A renderer backed by a ROW_MAJOR / 8-bit canvas, so every pixel maps to one
 * byte in the dump and can be asserted directly.
 */
function gfxRenderer(int $width, int $height): GFXRenderer
{
    return new GFXRenderer(
        new FullFrameBuffer($width, $height, new FormatSpec(PixelFormat::ROW_MAJOR, BitDepth::B8))
    );
}

/**
 * The canvas as a flat row-major list of pixel values.
 *
 * @return array<int, int>
 */
function gfxPixels(GFXRenderer $renderer): array
{
    return $renderer->buffer()->dump()[0]->raw_data;
}

/**
 * One pixel value, addressed in physical (buffer) coordinates.
 */
function gfxPixel(GFXRenderer $renderer, int $x, int $y): int
{
    return gfxPixels($renderer)[($y * $renderer->buffer()->viewportWidth()) + $x];
}

/**
 * Count of non-zero (painted) pixels on the canvas.
 */
function gfxPaintedCount(GFXRenderer $renderer): int
{
    return count(array_filter(gfxPixels($renderer), fn (int $value) => $value !== 0));
}
