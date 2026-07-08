<?php

use BareMetal\Contracts\Framebuffers\DTO\FormatSpec;
use BareMetal\Contracts\Framebuffers\Enums\BitDepth;
use BareMetal\Contracts\Framebuffers\Enums\BitOrder;
use BareMetal\Contracts\Framebuffers\Enums\PageAxis;
use BareMetal\Contracts\Framebuffers\Enums\PixelFormat;
use BareMetal\Framebuffers\FullFramebuffer;
use Microscrap\GFX\PhpdaFruit\PhpdafruitGFX;

/**
 * Golden-output parity with the published GFXRenderer v0.4.1: the fixed
 * README scene must keep producing byte-identical packed dumps. The hashes
 * were captured by running the identical scene through the old package.
 */
function parityScene(PhpdafruitGFX $renderer, int $bg, int $fg, int $accent, int $text, string $label): string
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
        $bytes = array_merge($bytes, $frame->raw_data);
    }

    return md5(json_encode($bytes));
}

it('matches the old renderer byte-for-byte on a mono vertical-page panel', function () {
    $renderer = new PhpdafruitGFX(new FullFramebuffer(128, 64, new FormatSpec(
        PixelFormat::MONO_VERTICAL_PAGE,
        BitDepth::B1,
        bit_order: BitOrder::LSB_FIRST,
        page_axis: PageAxis::VERTICAL,
    )));

    expect(parityScene($renderer, 0, 1, 1, 1, 'SCRAP'))
        ->toBe('b7d4f4992324a3947f66419f7901a236');
});

it('matches the old renderer byte-for-byte on a row-major RGB565 panel', function () {
    $renderer = new PhpdafruitGFX(new FullFramebuffer(160, 128, new FormatSpec(
        PixelFormat::ROW_MAJOR,
        BitDepth::B16,
    )));

    expect(parityScene($renderer, 0x0000, 0xFFFF, 0xF800, 0x07E0, 'ST7735'))
        ->toBe('c06aa17dd1f41d373c844b64fd185db0');
});
