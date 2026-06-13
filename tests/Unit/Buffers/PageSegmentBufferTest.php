<?php

use Microscrap\GFX\PhpdaFruit\Buffers\PageSegmentBuffer;
use ScrapyardIO\NutsAndBolts\DataObjects\FormatSpec;
use ScrapyardIO\NutsAndBolts\Enums\BitDepth;
use ScrapyardIO\NutsAndBolts\Enums\BitOrder;
use ScrapyardIO\NutsAndBolts\Enums\PixelFormat;
use ScrapyardIO\NutsAndBolts\Enums\RenderType;

function pageBuffer(int $width = 8, int $height = 16): PageSegmentBuffer
{
    return new PageSegmentBuffer(
        $width,
        $height,
        new FormatSpec(PixelFormat::MONO_VERTICAL_PAGE, BitDepth::B1, bit_order: BitOrder::LSB_FIRST),
    );
}

it('emits nothing when no writes happened', function () {
    expect(pageBuffer()->dump())->toBe([]);
});

it('ships only the touched page as a partial update', function () {
    $buffer = pageBuffer(8, 16);
    $buffer->setPixel(0, 0, 1);

    $updates = $buffer->dump();

    expect($updates)->toHaveCount(1)
        ->and($updates[0]->render_type)->toBe(RenderType::PARTIAL)
        ->and($updates[0]->origin_y)->toBe(0)
        ->and($updates[0]->width)->toBe(8)
        ->and($updates[0]->height)->toBe(8)
        ->and($updates[0]->raw_data[0])->toBe(1);
});

it('addresses a lower page by its row origin', function () {
    $buffer = pageBuffer(8, 16);
    $buffer->setPixel(0, 8, 1);

    $updates = $buffer->dump();

    expect($updates)->toHaveCount(1)
        ->and($updates[0]->origin_y)->toBe(8)
        ->and($updates[0]->raw_data[0])->toBe(1);
});

it('emits one update per dirty page in order', function () {
    $buffer = pageBuffer(8, 16);
    $buffer->setPixel(0, 8, 1)->setPixel(0, 0, 1);

    $origins = array_map(fn ($update) => $update->origin_y, $buffer->dump());

    expect($origins)->toBe([0, 8]);
});

it('clears the dirty set after dumping', function () {
    $buffer = pageBuffer(8, 16);
    $buffer->setPixel(0, 0, 1);
    $buffer->dump();

    expect($buffer->dump())->toBe([]);
});

it('repaints every page when marked all dirty', function () {
    $buffer = pageBuffer(8, 16);

    expect($buffer->markAllDirty()->dump())->toHaveCount(2);
});

it('clamps the final partial page to the remaining rows', function () {
    $buffer = pageBuffer(8, 12);
    $buffer->markAllDirty();

    $updates = $buffer->dump();

    expect($updates[1]->origin_y)->toBe(8)
        ->and($updates[1]->height)->toBe(4);
});

it('flush clears the retained canvas', function () {
    $buffer = pageBuffer(8, 16);
    $buffer->setPixel(0, 0, 1)->flush();

    // After flush the canvas is blank, so a forced repaint carries only zero bytes.
    $bytes = $buffer->markAllDirty()->dump()[0]->raw_data;

    expect(array_filter($bytes))->toBe([]);
});
