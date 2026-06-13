<?php

it('outlines a rectangle without filling it', function () {
    $renderer = gfxRenderer(8, 8);
    $renderer->drawRect(1, 1, 4, 3, 5);

    // Perimeter of a 4x3 box = 10 pixels, interior stays blank.
    expect(gfxPaintedCount($renderer))->toBe(10)
        ->and(gfxPixel($renderer, 1, 1))->toBe(5)
        ->and(gfxPixel($renderer, 4, 3))->toBe(5)
        ->and(gfxPixel($renderer, 2, 2))->toBe(0);
});

it('fills a rectangle solid', function () {
    $renderer = gfxRenderer(8, 8);
    $renderer->fillRect(1, 1, 3, 2, 5);

    expect(gfxPaintedCount($renderer))->toBe(6)
        ->and(gfxPixel($renderer, 1, 1))->toBe(5)
        ->and(gfxPixel($renderer, 3, 2))->toBe(5);
});

it('fills the whole screen', function () {
    $renderer = gfxRenderer(3, 2);
    $renderer->fillScreen(5);

    expect(gfxPaintedCount($renderer))->toBe(6);
});

it('rounds the corners of a drawn round-rect', function () {
    $renderer = gfxRenderer(8, 8);
    $renderer->drawRoundRect(0, 0, 8, 8, 2, 5);

    // The very corner is clipped off by the radius; the edge midpoints stay.
    expect(gfxPixel($renderer, 0, 0))->toBe(0)
        ->and(gfxPixel($renderer, 4, 0))->toBe(5)
        ->and(gfxPixel($renderer, 0, 4))->toBe(5);
});

it('fills a round-rect leaving the corners clear', function () {
    $renderer = gfxRenderer(8, 8);
    $renderer->fillRoundRect(0, 0, 8, 8, 2, 5);

    expect(gfxPixel($renderer, 0, 0))->toBe(0)
        ->and(gfxPixel($renderer, 4, 4))->toBe(5);
});

it('treats drawFillRect as an alias for fillRect', function () {
    $renderer = gfxRenderer(8, 8);
    $renderer->drawFillRect(0, 0, 2, 2, 5);

    expect(gfxPaintedCount($renderer))->toBe(4);
});
