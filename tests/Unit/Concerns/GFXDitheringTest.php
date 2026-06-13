<?php

it('lights a cell at or above the threshold', function () {
    expect(gfxRenderer(4, 4)->ditherFloydSteinberg([200], 1, 1))->toBe([1]);
});

it('leaves a cell below the threshold unlit', function () {
    expect(gfxRenderer(4, 4)->ditherFloydSteinberg([100], 1, 1))->toBe([0]);
});

it('uses the supplied on and off values', function () {
    $renderer = gfxRenderer(4, 4);

    expect($renderer->ditherFloydSteinberg([200], 1, 1, 128, 9, 3))->toBe([9])
        ->and($renderer->ditherFloydSteinberg([100], 1, 1, 128, 9, 3))->toBe([3]);
});

it('diffuses quantisation error to the next cell', function () {
    // 130 lights (>=128); its -125 error pushes the neighbour well below threshold.
    expect(gfxRenderer(4, 4)->ditherFloydSteinberg([130, 0], 2, 1))->toBe([1, 0]);
});

it('does not mutate the source map', function () {
    $renderer = gfxRenderer(4, 4);
    $map = [200, 50];
    $renderer->ditherFloydSteinberg($map, 2, 1);

    expect($map)->toBe([200, 50]);
});

it('rejects non-positive dimensions', function () {
    expect(fn () => gfxRenderer(4, 4)->ditherFloydSteinberg([], 0, 0))
        ->toThrow(InvalidArgumentException::class);
});
