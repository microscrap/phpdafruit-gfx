<?php

it('draws a horizontal run', function () {
    $renderer = gfxRenderer(8, 8);
    $renderer->drawHorizontalLine(2, 3, 4, 5);

    expect(gfxPaintedCount($renderer))->toBe(4)
        ->and(gfxPixel($renderer, 2, 3))->toBe(5)
        ->and(gfxPixel($renderer, 5, 3))->toBe(5)
        ->and(gfxPixel($renderer, 1, 3))->toBe(0)
        ->and(gfxPixel($renderer, 6, 3))->toBe(0);
});

it('draws a vertical run', function () {
    $renderer = gfxRenderer(8, 8);
    $renderer->drawVerticalLine(3, 1, 4, 6);

    expect(gfxPaintedCount($renderer))->toBe(4)
        ->and(gfxPixel($renderer, 3, 1))->toBe(6)
        ->and(gfxPixel($renderer, 3, 4))->toBe(6);
});

it('routes an axis-aligned drawLine to the straight-run path', function () {
    $renderer = gfxRenderer(8, 8);
    // Reversed endpoints should normalise to the same vertical run.
    $renderer->drawLine(2, 5, 2, 1, 7);

    expect(gfxPaintedCount($renderer))->toBe(5)
        ->and(gfxPixel($renderer, 2, 1))->toBe(7)
        ->and(gfxPixel($renderer, 2, 5))->toBe(7);
});

it('walks a 45 degree diagonal pixel by pixel', function () {
    $renderer = gfxRenderer(4, 4);
    $renderer->drawLine(0, 0, 3, 3, 4);

    expect(gfxPaintedCount($renderer))->toBe(4)
        ->and(gfxPixel($renderer, 0, 0))->toBe(4)
        ->and(gfxPixel($renderer, 1, 1))->toBe(4)
        ->and(gfxPixel($renderer, 2, 2))->toBe(4)
        ->and(gfxPixel($renderer, 3, 3))->toBe(4);
});

it('skips a line entirely outside the viewport', function () {
    $renderer = gfxRenderer(6, 6);
    $renderer->drawLine(-5, -5, -1, -1, 5);

    expect(gfxPaintedCount($renderer))->toBe(0);
});

it('clips a horizontal line that starts off the left edge', function () {
    $renderer = gfxRenderer(6, 6);
    $renderer->drawLine(-2, 1, 2, 1, 5);

    expect(gfxPaintedCount($renderer))->toBe(3)
        ->and(gfxPixel($renderer, 0, 1))->toBe(5)
        ->and(gfxPixel($renderer, 2, 1))->toBe(5);
});

it('normalises a negative width horizontal line', function () {
    $renderer = gfxRenderer(8, 8);
    $renderer->drawHorizontalLine(5, 0, -3, 9);

    expect(gfxPaintedCount($renderer))->toBe(3)
        ->and(gfxPixel($renderer, 3, 0))->toBe(9)
        ->and(gfxPixel($renderer, 5, 0))->toBe(9);
});
