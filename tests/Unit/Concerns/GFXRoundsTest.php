<?php

it('draws a circle outline through its cardinal points', function () {
    $renderer = gfxRenderer(9, 9);
    $renderer->drawCircle(4, 4, 3, 5);

    expect(gfxPixel($renderer, 4, 7))->toBe(5)
        ->and(gfxPixel($renderer, 4, 1))->toBe(5)
        ->and(gfxPixel($renderer, 7, 4))->toBe(5)
        ->and(gfxPixel($renderer, 1, 4))->toBe(5)
        ->and(gfxPixel($renderer, 4, 4))->toBe(0);
});

it('fills a circle through its centre', function () {
    $renderer = gfxRenderer(9, 9);
    $renderer->fillCircle(4, 4, 3, 5);

    expect(gfxPixel($renderer, 4, 4))->toBe(5)
        ->and(gfxPixel($renderer, 4, 1))->toBe(5)
        ->and(gfxPixel($renderer, 1, 1))->toBe(0);
});

it('skips a circle that is entirely off-screen', function () {
    $renderer = gfxRenderer(8, 8);
    $renderer->drawCircle(-10, -10, 2, 5);

    expect(gfxPaintedCount($renderer))->toBe(0);
});

it('draws an ellipse outline at its extents', function () {
    $renderer = gfxRenderer(11, 9);
    $renderer->drawEllipse(5, 4, 4, 3, 5);

    expect(gfxPixel($renderer, 9, 4))->toBe(5)
        ->and(gfxPixel($renderer, 1, 4))->toBe(5)
        ->and(gfxPixel($renderer, 5, 7))->toBe(5)
        ->and(gfxPixel($renderer, 5, 1))->toBe(5)
        ->and(gfxPixel($renderer, 5, 4))->toBe(0);
});

it('fills an ellipse through its centre', function () {
    $renderer = gfxRenderer(11, 9);
    $renderer->fillEllipse(5, 4, 4, 3, 5);

    expect(gfxPixel($renderer, 5, 4))->toBe(5)
        ->and(gfxPixel($renderer, 9, 4))->toBe(5);
});
