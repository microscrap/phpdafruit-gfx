<?php

use Microscrap\GFX\PhpdaFruit\Buffers\DirtyRegionsBuffer;
use Microscrap\GFX\PhpdaFruit\Buffers\PageSegmentBuffer;
use ScrapyardIO\NutsAndBolts\Enums\BitDepth;
use ScrapyardIO\NutsAndBolts\Enums\BitOrder;
use ScrapyardIO\NutsAndBolts\Enums\PixelFormat;

it('builds a page-segment buffer through its factory', function () {
    $buffer = PageSegmentBuffer::size(8, 16)
        ->pixelFormat(PixelFormat::MONO_VERTICAL_PAGE)
        ->bitDepth(BitDepth::B1)
        ->bitOrder(BitOrder::LSB_FIRST)
        ->build();

    expect($buffer)->toBeInstanceOf(PageSegmentBuffer::class)
        ->and($buffer->viewportWidth())->toBe(8)
        ->and($buffer->viewportHeight())->toBe(16);
});

it('builds a dirty-regions buffer through its factory', function () {
    $buffer = DirtyRegionsBuffer::size(4, 4)
        ->pixelFormat(PixelFormat::ROW_MAJOR)
        ->bitDepth(BitDepth::B8)
        ->build();

    expect($buffer)->toBeInstanceOf(DirtyRegionsBuffer::class)
        ->and($buffer->viewportWidth())->toBe(4);
});

it('requires a pixel format before building', function () {
    expect(fn () => DirtyRegionsBuffer::size(4, 4)->build())
        ->toThrow(Exception::class, 'Missing pixel format.');
});

it('requires a bit depth before building', function () {
    expect(fn () => DirtyRegionsBuffer::size(4, 4)->pixelFormat(PixelFormat::ROW_MAJOR)->build())
        ->toThrow(Exception::class, 'Missing bit depth.');
});
