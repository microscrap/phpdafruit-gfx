<?php

it('outlines a triangle along its three edges', function () {
    $renderer = gfxRenderer(6, 6);
    $renderer->drawTriangle(0, 0, 4, 0, 0, 4, 5);

    expect(gfxPixel($renderer, 0, 0))->toBe(5)
        ->and(gfxPixel($renderer, 4, 0))->toBe(5)
        ->and(gfxPixel($renderer, 0, 4))->toBe(5)
        // (1,1) is inside the outline, off every edge.
        ->and(gfxPixel($renderer, 1, 1))->toBe(0);
});

it('fills the interior of a triangle', function () {
    $renderer = gfxRenderer(6, 6);
    $renderer->fillTriangle(0, 0, 4, 0, 0, 4, 5);

    expect(gfxPixel($renderer, 0, 0))->toBe(5)
        ->and(gfxPixel($renderer, 1, 1))->toBe(5)
        // Beyond the x+y=4 hypotenuse, nothing is painted.
        ->and(gfxPixel($renderer, 3, 3))->toBe(0);
});

it('skips a triangle that sits above the viewport', function () {
    $renderer = gfxRenderer(6, 6);
    $renderer->fillTriangle(0, -5, 4, -5, 2, -1, 5);

    expect(gfxPaintedCount($renderer))->toBe(0);
});

it('fills a degenerate flat triangle as a single run', function () {
    $renderer = gfxRenderer(6, 6);
    $renderer->fillTriangle(0, 0, 4, 0, 2, 0, 5);

    expect(gfxPaintedCount($renderer))->toBe(5)
        ->and(gfxPixel($renderer, 0, 0))->toBe(5)
        ->and(gfxPixel($renderer, 4, 0))->toBe(5);
});
