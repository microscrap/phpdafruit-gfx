<?php

use Microscrap\GFX\PhpdaFruit\Buffers\DirtyRegionsBuffer;
use ScrapyardIO\NutsAndBolts\DataObjects\FormatSpec;
use ScrapyardIO\NutsAndBolts\Enums\BitDepth;
use ScrapyardIO\NutsAndBolts\Enums\Endianness;
use ScrapyardIO\NutsAndBolts\Enums\PixelFormat;
use ScrapyardIO\NutsAndBolts\Enums\RenderType;

function dirtyBuffer(int $width = 8, int $height = 8, ?FormatSpec $spec = null): DirtyRegionsBuffer
{
    return new DirtyRegionsBuffer($width, $height, $spec ?? new FormatSpec(PixelFormat::ROW_MAJOR, BitDepth::B8));
}

it('emits nothing when no writes happened', function () {
    expect(dirtyBuffer()->dump())->toBe([]);
});

it('ships a single dirty rectangle as one partial update', function () {
    $buffer = dirtyBuffer(4, 4);
    $buffer->setSegment(1, 1, 2, 2, 9);

    $updates = $buffer->dump();

    expect($updates)->toHaveCount(1)
        ->and($updates[0]->render_type)->toBe(RenderType::PARTIAL)
        ->and($updates[0]->origin_x)->toBe(1)
        ->and($updates[0]->origin_y)->toBe(1)
        ->and($updates[0]->width)->toBe(2)
        ->and($updates[0]->height)->toBe(2)
        ->and($updates[0]->raw_data)->toBe([9, 9, 9, 9]);
});

it('coalesces touching writes into a single region', function () {
    $buffer = dirtyBuffer(8, 4);
    $buffer->setPixel(0, 0, 1)->setPixel(1, 0, 2);

    $updates = $buffer->dump();

    expect($updates)->toHaveCount(1)
        ->and($updates[0]->origin_x)->toBe(0)
        ->and($updates[0]->width)->toBe(2)
        ->and($updates[0]->height)->toBe(1)
        ->and($updates[0]->raw_data)->toBe([1, 2]);
});

it('keeps disjoint writes as separate regions', function () {
    $buffer = dirtyBuffer(8, 4);
    $buffer->setPixel(0, 0, 1)->setPixel(5, 0, 2);

    $updates = $buffer->dump();

    $origins = array_map(fn ($update) => $update->origin_x, $updates);
    sort($origins);

    expect($updates)->toHaveCount(2)
        ->and($origins)->toBe([0, 5]);
});

it('clears the dirty set after dumping', function () {
    $buffer = dirtyBuffer(4, 4);
    $buffer->setPixel(0, 0, 1);

    $buffer->dump();

    expect($buffer->dump())->toBe([]);
});

it('marks the whole surface dirty as one region', function () {
    $buffer = dirtyBuffer(3, 2);
    $buffer->markAllDirty();

    $updates = $buffer->dump();

    expect($updates)->toHaveCount(1)
        ->and($updates[0]->width)->toBe(3)
        ->and($updates[0]->height)->toBe(2);
});

it('splits multi-byte pixels high byte first', function () {
    $spec = new FormatSpec(PixelFormat::ROW_MAJOR, BitDepth::B16, endianness: Endianness::MSB);
    $buffer = dirtyBuffer(2, 1, $spec);
    $buffer->setPixel(0, 0, 0xF800)->setPixel(1, 0, 0x07E0);

    $updates = $buffer->dump();

    expect($updates)->toHaveCount(1)
        ->and($updates[0]->raw_data)->toBe([0xF8, 0x00, 0x07, 0xE0]);
});
