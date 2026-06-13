<?php

it('rejects a missing image file', function () {
    // ext-gd is checked before the file lookup, so the thrown type depends on it.
    $expected = extension_loaded('gd') ? InvalidArgumentException::class : RuntimeException::class;

    expect(fn () => gfxRenderer(8, 8)->drawImageFromFile('/no/such/image.png'))
        ->toThrow($expected);
});

it('refuses an unsupported extension as a load failure', function () {
    $path = tempnam(sys_get_temp_dir(), 'gfx').'.tiff';
    file_put_contents($path, 'not really an image');

    try {
        expect(fn () => gfxRenderer(8, 8)->drawImageFromFile($path))
            ->toThrow(RuntimeException::class);
    } finally {
        @unlink($path);
    }
})->skip(! extension_loaded('gd'), 'requires ext-gd');

it('decodes and draws an image file', function () {
    $path = tempnam(sys_get_temp_dir(), 'gfx').'.png';
    $image = imagecreatetruecolor(2, 2);
    $white = imagecolorallocate($image, 255, 255, 255);
    imagefilledrectangle($image, 0, 0, 1, 1, $white);
    imagepng($image, $path);
    imagedestroy($image);

    try {
        $renderer = gfxRenderer(2, 2);
        $renderer->drawImageFromFile($path, 0, 0, 2, 2);

        // White maps to grayscale 255, so all four pixels should be painted.
        expect(gfxPaintedCount($renderer))->toBe(4);
    } finally {
        @unlink($path);
    }
})->skip(! extension_loaded('gd'), 'requires ext-gd');
