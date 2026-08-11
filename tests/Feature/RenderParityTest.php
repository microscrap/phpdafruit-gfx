<?php

use ScrapyardIO\Tubes\Contracts\Framebuffers\Enums\BitDepth;
use ScrapyardIO\Tubes\Contracts\Framebuffers\Enums\BitOrder;
use ScrapyardIO\Tubes\Contracts\Framebuffers\Enums\PageAxis;
use ScrapyardIO\Tubes\Contracts\Framebuffers\Enums\PixelFormat;
use ScrapyardIO\Tubes\Contracts\Framebuffers\FormatSpec;
use ScrapyardIO\Tubes\Framebuffers\FullFramebuffer;
use Microscrap\GFX\PhpdaFruit\PhpdafruitRenderer2D;

/**
 * Golden-output parity with the published GFXRenderer v0.4.1: the fixed
 * README scene must keep producing byte-identical packed dumps. The hashes
 * were captured by running the identical scene through the old package.
 */
function parityScene(PhpdafruitRenderer2D $renderer, int $bg, int $fg, int $accent, int $text, string $label): string
{
    $renderer
        ->fill($bg)
        ->drawRoundRect(0, 0, $renderer->width, $renderer->height, 6, $fg)
        ->fillCircle(96, 38, 12, $accent)
        ->setTextColor($text)
        ->setCursor(6, 4)
        ->print($label);

    $bytes = [];
    foreach ($renderer->render() as $frame) {
        $bytes = array_merge($bytes, array_values(unpack('C*', $frame->raw_data) ?: []));
    }

    return md5(json_encode($bytes));
}

it('matches the old renderer byte-for-byte on a mono vertical-page panel', function () {
    $buffer = FullFramebuffer::sized(128, 64, new FormatSpec(
        PixelFormat::MONO_VERTICAL_PAGE,
        BitDepth::B1,
        bit_order: BitOrder::LSB_FIRST,
        page_axis: PageAxis::VERTICAL,
    ));
    $renderer = new PhpdafruitRenderer2D($buffer);

    expect(parityScene($renderer, 0, 1, 1, 1, 'SCRAP'))
        ->toBe('b7d4f4992324a3947f66419f7901a236');
});

it('matches the old renderer byte-for-byte on a row-major RGB565 panel', function () {
    $buffer = FullFramebuffer::sized(160, 128, new FormatSpec(
        PixelFormat::ROW_MAJOR,
        BitDepth::B16,
    ));
    $renderer = new PhpdafruitRenderer2D($buffer);

    expect(parityScene($renderer, 0x0000, 0xFFFF, 0xF800, 0x07E0, 'ST7735'))
        ->toBe('c06aa17dd1f41d373c844b64fd185db0');
});
