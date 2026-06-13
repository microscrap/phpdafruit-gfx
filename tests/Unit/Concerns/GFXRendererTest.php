<?php

it('sets a single pixel', function () {
    $renderer = gfxRenderer(4, 4);
    $renderer->drawPixel(1, 2, 7);

    expect(gfxPixel($renderer, 1, 2))->toBe(7)
        ->and(gfxPaintedCount($renderer))->toBe(1);
});

it('drops pixels drawn outside the surface', function () {
    $renderer = gfxRenderer(4, 4);
    $renderer->drawPixel(-1, 0, 7)
        ->drawPixel(4, 0, 7)
        ->drawPixel(0, 4, 7);

    expect(gfxPaintedCount($renderer))->toBe(0);
});

it('fills a clipped segment', function () {
    $renderer = gfxRenderer(4, 4);
    $renderer->drawSegment(2, 2, 10, 10, 5);

    // Only the 2x2 on-surface corner should paint.
    expect(gfxPaintedCount($renderer))->toBe(4)
        ->and(gfxPixel($renderer, 3, 3))->toBe(5);
});

it('paints many pixels at once', function () {
    $renderer = gfxRenderer(4, 4);
    $renderer->drawPixels([[0, 0, 1], [1, 1, 2], [2, 2, 3]]);

    expect(gfxPixel($renderer, 0, 0))->toBe(1)
        ->and(gfxPixel($renderer, 1, 1))->toBe(2)
        ->and(gfxPixel($renderer, 2, 2))->toBe(3);
});

it('fills the whole canvas', function () {
    $renderer = gfxRenderer(3, 2);
    $renderer->fill(9);

    expect(gfxPixels($renderer))->toBe([9, 9, 9, 9, 9, 9]);
});

it('defaults to no rotation', function () {
    expect(gfxRenderer(4, 4)->getRotation())->toBe(0);
});

it('rejects rotations outside 0-3', function () {
    expect(fn () => gfxRenderer(4, 4)->setRotation(4))->toThrow(OutOfBoundsException::class);
});

it('swaps width and height for odd rotations', function () {
    $renderer = gfxRenderer(4, 3);

    $renderer->setRotation(0);
    expect($renderer->width())->toBe(4)->and($renderer->height())->toBe(3);

    $renderer->setRotation(1);
    expect($renderer->width())->toBe(3)->and($renderer->height())->toBe(4);
});

it('maps logical origin through a 180 degree rotation', function () {
    $renderer = gfxRenderer(4, 3);
    $renderer->setRotation(2);
    $renderer->drawPixel(0, 0, 7);

    // 180°: logical (0,0) lands at the physical bottom-right corner.
    expect(gfxPixel($renderer, 3, 2))->toBe(7);
});
