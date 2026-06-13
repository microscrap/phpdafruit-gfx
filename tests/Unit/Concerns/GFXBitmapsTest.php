<?php

it('draws a 1bpp bitmap MSB first', function () {
    $renderer = gfxRenderer(8, 8);
    // 0x81 = 1000_0001 -> leftmost and rightmost columns lit.
    $renderer->drawBitmap(0, 0, [0x81], 8, 1, 5);

    expect(gfxPaintedCount($renderer))->toBe(2)
        ->and(gfxPixel($renderer, 0, 0))->toBe(5)
        ->and(gfxPixel($renderer, 7, 0))->toBe(5);
});

it('paints a background then the bitmap foreground', function () {
    $renderer = gfxRenderer(8, 8);
    $renderer->drawBitmap(0, 0, [0x80], 8, 1, 5, 9);

    expect(gfxPaintedCount($renderer))->toBe(8)
        ->and(gfxPixel($renderer, 0, 0))->toBe(5)
        ->and(gfxPixel($renderer, 1, 0))->toBe(9);
});

it('draws an XBM bitmap LSB first', function () {
    $renderer = gfxRenderer(8, 8);
    // 0x02 = 0000_0010 -> with LSB-first ordering only column 1 is lit.
    $renderer->drawXBitmap(0, 0, [0x02], 8, 1, 5);

    expect(gfxPaintedCount($renderer))->toBe(1)
        ->and(gfxPixel($renderer, 1, 0))->toBe(5);
});

it('draws a grayscale bitmap pixel for pixel', function () {
    $renderer = gfxRenderer(8, 8);
    $renderer->drawGrayscaleBitmap(0, 0, [10, 20, 30, 40], 2, 2);

    expect(gfxPixel($renderer, 0, 0))->toBe(10)
        ->and(gfxPixel($renderer, 1, 0))->toBe(20)
        ->and(gfxPixel($renderer, 0, 1))->toBe(30)
        ->and(gfxPixel($renderer, 1, 1))->toBe(40);
});

it('skips null cells in a colour map', function () {
    $renderer = gfxRenderer(8, 8);
    $renderer->drawColorMap(0, 0, [5, null, null, 7], 2, 2);

    expect(gfxPaintedCount($renderer))->toBe(2)
        ->and(gfxPixel($renderer, 0, 0))->toBe(5)
        ->and(gfxPixel($renderer, 1, 1))->toBe(7);
});

it('skips a bitmap drawn entirely off-screen', function () {
    $renderer = gfxRenderer(8, 8);
    $renderer->drawBitmap(20, 20, [0xFF], 8, 1, 5);

    expect(gfxPaintedCount($renderer))->toBe(0);
});
