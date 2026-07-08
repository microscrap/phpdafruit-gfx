<?php

use BareMetal\Contracts\Framebuffers\DTO\FormatSpec;
use BareMetal\Contracts\Framebuffers\Enums\BitDepth;
use BareMetal\Contracts\Framebuffers\Enums\BitOrder;
use BareMetal\Contracts\Framebuffers\Enums\PageAxis;
use BareMetal\Contracts\Framebuffers\Enums\PixelFormat;
use BareMetal\Contracts\Framebuffers\Enums\RenderType;
use BareMetal\Framebuffers\DirtyRegionsBuffer;
use BareMetal\Framebuffers\FullFramebuffer;
use BareMetal\Framebuffers\PageSegmentBuffer;
use Microscrap\GFX\PhpdaFruit\PhpdafruitGFX;

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

it('exposes rotation, width and height as magic properties', function () {
    $renderer = gfxRenderer(4, 3);

    expect($renderer->rotation)->toBe(0)
        ->and($renderer->width)->toBe(4)
        ->and($renderer->height)->toBe(3);

    $renderer->rotation = 1;

    expect($renderer->rotation)->toBe(1)
        ->and($renderer->width)->toBe(3)
        ->and($renderer->height)->toBe(4);
});

it('rejects unknown magic properties', function () {
    expect(fn () => gfxRenderer(4, 4)->nope)->toThrow(RuntimeException::class)
        ->and(function () {
            gfxRenderer(4, 4)->nope = 1;
        })->toThrow(RuntimeException::class);
});

it('renders the framebuffer dump', function () {
    $renderer = gfxRenderer(2, 2);
    $renderer->drawPixel(0, 0, 3);

    $frames = $renderer->render();

    expect($frames)->toHaveCount(1)
        ->and($frames[0]->render_type)->toBe(RenderType::FULL)
        ->and($frames[0]->raw_data)->toBe([3, 0, 0, 0]);
});

it('prefers a page-segment buffer for vertical-page mono specs', function () {
    $spec = new FormatSpec(
        PixelFormat::MONO_VERTICAL_PAGE,
        BitDepth::B1,
        bit_order: BitOrder::LSB_FIRST,
        page_axis: PageAxis::VERTICAL,
    );

    $buffer = PhpdafruitGFX::preferredFramebuffer($spec, 128, 64);

    expect($buffer)->toBeInstanceOf(PageSegmentBuffer::class)
        ->and($buffer->viewportWidth())->toBe(128)
        ->and($buffer->viewportHeight())->toBe(64);
});

it('prefers a dirty-regions buffer for row-major specs', function () {
    $spec = new FormatSpec(PixelFormat::ROW_MAJOR, BitDepth::B16);

    expect(PhpdafruitGFX::preferredFramebuffer($spec, 160, 128))
        ->toBeInstanceOf(DirtyRegionsBuffer::class);
});

it('falls back to a full framebuffer for other specs', function () {
    $spec = new FormatSpec(PixelFormat::MONO_HORIZONTAL, BitDepth::B1, bit_order: BitOrder::MSB_FIRST);

    expect(PhpdafruitGFX::preferredFramebuffer($spec, 8, 8))
        ->toBeInstanceOf(FullFramebuffer::class);
});
