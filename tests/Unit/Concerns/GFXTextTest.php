<?php

use BareMetal\GFX\Fonts\GFXFont;

/**
 * A minimal Adafruit-encoded 1bpp font: one 2x2 solid glyph for 'A',
 * baseline-relative yOffset of -2, x advance of 3.
 */
function gfxTestFont(): GFXFont
{
    return new class extends GFXFont
    {
        protected int $first = 65;

        protected int $last = 65;

        protected int $yAdvance = 10;

        protected string $fontEncoding = 'adafruit';

        protected array $bitmaps = [0xF0];

        protected array $glyphs = [
            [0, 2, 2, 3, 0, -2],
        ];
    };
}

it('moves and reports the cursor', function () {
    $renderer = gfxRenderer(32, 32);
    $renderer->setCursor(7, 9);

    expect($renderer->getCursorX())->toBe(7)
        ->and($renderer->getCursorY())->toBe(9);
});

it('clamps a non-positive text size to one', function () {
    $renderer = gfxRenderer(32, 32);
    $renderer->setTextSize(0);

    // size 1 cell is 6px wide, so one glyph advances the cursor by 6.
    $renderer->setTextColor(5)->setCursor(0, 0)->print('A');

    expect($renderer->getCursorX())->toBe(6);
});

it('advances the cursor six pixels per classic glyph', function () {
    $renderer = gfxRenderer(32, 32);
    $renderer->setTextColor(5)->setCursor(0, 0)->print('AB');

    expect($renderer->getCursorX())->toBe(12);
});

it('paints glyph pixels for a printed character', function () {
    $renderer = gfxRenderer(32, 32);
    $renderer->setTextColor(5)->setCursor(0, 0)->print('A');

    // The glyph lives in the 6x8 cell at the origin and is not blank.
    expect(gfxPaintedCount($renderer))->toBeGreaterThan(0);
});

it('starts a new line on a newline byte', function () {
    $renderer = gfxRenderer(32, 32);
    $renderer->setTextColor(5)->setCursor(10, 0)->println('A');

    expect($renderer->getCursorX())->toBe(0)
        ->and($renderer->getCursorY())->toBe(8);
});

it('scales the cell with a larger text size', function () {
    $renderer = gfxRenderer(32, 32);
    $renderer->setTextSize(2, 3)->setTextColor(5)->setCursor(0, 0)->print('A');

    // 6px * size_x(2) = 12px advance.
    expect($renderer->getCursorX())->toBe(12);
});

it('measures the bounds of a classic-font string', function () {
    $renderer = gfxRenderer(64, 32);
    $bounds = $renderer->getTextBounds('AB', 0, 0);

    expect($bounds['w'])->toBe(12)
        ->and($bounds['h'])->toBe(8);
});

it('decodes an agnostic custom font glyph baseline-relative', function () {
    $renderer = gfxRenderer(16, 16);
    $renderer->setFont(gfxTestFont())->setTextColor(5)->setCursor(1, 2)->print('A');

    // The 2x2 glyph sits yOffset(-2) above the baseline at (1,2).
    expect(gfxPaintedCount($renderer))->toBe(4)
        ->and(gfxPixel($renderer, 1, 0))->toBe(5)
        ->and(gfxPixel($renderer, 2, 1))->toBe(5)
        ->and($renderer->getCursorX())->toBe(4);
});

it('skips characters a custom font does not cover', function () {
    $renderer = gfxRenderer(16, 16);
    $renderer->setFont(gfxTestFont())->setTextColor(5)->setCursor(0, 8)->print('Z');

    expect(gfxPaintedCount($renderer))->toBe(0);
});

it('measures the bounds of a custom-font string', function () {
    $renderer = gfxRenderer(64, 32);
    $renderer->setFont(gfxTestFont());

    $bounds = $renderer->getTextBounds('A', 0, 10);

    expect($bounds['w'])->toBe(2)
        ->and($bounds['h'])->toBe(2)
        ->and($bounds['y1'])->toBe(8);
});
