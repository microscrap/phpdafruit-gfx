# microscrap/phpdafruit-gfx - Adafruit-GFX-style rendering for ScrapyardIO

[![Coverage](https://img.shields.io/badge/coverage-64.1%25-orange)](#testing-pest-v4)
[![Tests](https://img.shields.io/badge/tests-75%20passing-brightgreen)](#testing-pest-v4)
[![PHP](https://img.shields.io/badge/php-%5E8.3-777bb4)](composer.json)
[![License](https://img.shields.io/badge/license-MIT-blue)](LICENSE.md)

A pure-PHP 2D graphics renderer modeled on [Adafruit-GFX](https://github.com/adafruit/Adafruit-GFX-Library), designed to work with the [ScrapyardIO framework](https://scrapyard-io.projectsaturnstudios.com). It draws into a `scrapyard-io/nuts-and-bolts` `FormatSpecFrameBuffer` and renders through the `scrapyard-io/reality-interface` display layer, so the same drawing API targets monochrome page panels (SSD1306/SH1106) and color TFTs (ST77xx) alike — the buffer's `FormatSpec` decides how pixels are packed on dump.

This package includes:

* A fluent `GFXRenderer` with the full GFX primitive set (pixels, lines, rects, round-rects, circles, ellipses, triangles, text, bitmaps, images, dithering)
* Rotation-aware drawing (0/90/180/270°)
* Partial-refresh buffers: `PageSegmentBuffer` (paged mono) and `DirtyRegionsBuffer` (rectangle-coalescing color TFT)
* A bundled `ClassicFont` plus a library of Adafruit/LVGL-style `PhpdafruitFont` glyph tables

## Requirements

* PHP 8.3+
* `scrapyard-io/nuts-and-bolts` ^0.4.1
* `scrapyard-io/reality-interface` ^0.4.1
* `ext-gd` *(suggested)* — only for `drawImageFromFile()` / `drawImageDithered()`. Raw pixel paths (`drawBitmap()`, `drawColorMap()`, `ditherFloydSteinberg()`) work without it.

## Installation

```bash
composer require microscrap/phpdafruit-gfx
```

## Usage

Construct a renderer over any `FormatSpecFrameBuffer`. The `FormatSpec` you give the buffer determines how `render()` packs the bytes for your panel.

```php
<?php

use Microscrap\GFX\PhpdaFruit\GFXRenderer;
use Microscrap\GFX\PhpdaFruit\Buffers\DirtyRegionsBuffer;
use ScrapyardIO\NutsAndBolts\DataObjects\FormatSpec;
use ScrapyardIO\NutsAndBolts\Enums\BitDepth;
use ScrapyardIO\NutsAndBolts\Enums\Endianness;
use ScrapyardIO\NutsAndBolts\Enums\PixelFormat;

// RGB565 color TFT surface with rectangle-coalescing partial refresh.
$buffer = new DirtyRegionsBuffer(
    160,
    128,
    new FormatSpec(PixelFormat::ROW_MAJOR, BitDepth::B16, endianness: Endianness::MSB),
);

$renderer = new GFXRenderer($buffer);

$renderer
    ->fill(0x0000)
    ->drawRoundRect(0, 0, 160, 128, 6, 0xFFFF)
    ->fillCircle(96, 38, 12, 0xF800)
    ->setTextColor(0x07E0)
    ->setCursor(6, 4)
    ->print('ST7735');

// One DumpedBuffer per changed region, already packed in the buffer's FormatSpec.
$updates = $renderer->render();
```

## API

### `Microscrap\GFX\PhpdaFruit\GFXRenderer`

**Surface & lifecycle**

* `__construct(FormatSpecFrameBuffer $buffer)`
* `buffer(): FormatSpecFrameBuffer`
* `render(): array` — dumps the buffer (one or more `DumpedBuffer`s)
* `width(): int`, `height(): int` — logical dimensions (swap under 90/270° rotation)
* `getRotation(): int`, `setRotation(int $rotation)` — `0–3`
* `$renderer->width`, `->height`, `->rotation` — read-only magic accessors

**Pixels & segments**

* `drawPixel(int $x, int $y, int $color)`
* `drawPixels(array $pixels)` — `[[x, y, color], ...]`
* `drawSegment(int $x, int $y, int $w, int $h, int $color)`
* `fill(int $color)`

**Lines** (`GFXLines`)

* `drawLine(int $x0, int $y0, int $x1, int $y1, int $color)`
* `drawHorizontalLine` / `drawHLine`, `drawVerticalLine` / `drawVLine`
* `drawLines(array $lines)` — `[[x0, y0, x1, y1, color], ...]`

**Rectangles** (`GFXRects`)

* `drawRect`, `fillRect`, `fillScreen`
* `drawRoundRect`, `fillRoundRect`

**Curves** (`GFXRounds`)

* `drawCircle`, `fillCircle`
* `drawEllipse`, `fillEllipse`

**Triangles** (`GFXTriangles`)

* `drawTriangle`, `fillTriangle`

**Text** (`GFXText`)

* `setCursor`, `getCursorX`, `getCursorY`
* `setTextSize`, `setTextColor`, `setTextWrap`, `setCp437`
* `setFont(?PhpdafruitFont $font)`
* `write(int $c)`, `print(string $str)`, `println(string $str = '')`, `drawChar(...)`
* `getTextBounds(string $str, int $x, int $y): array`

**Bitmaps** (`GFXBitmaps`)

* `drawBitmap` (MSB-first), `drawXBitmap` (LSB-first)
* `drawGrayscaleBitmap`, `drawColorMap`

**Images** (`GFXImages`, needs `ext-gd`)

* `drawImageFromFile(string $path, int $x = 0, int $y = 0, ?int $w = null, ?int $h = null)`

**Dithering** (`GFXDithering`)

* `ditherFloydSteinberg(array $grayMap, int $w, int $h, int $threshold = 128, int $on = 1, int $off = 0): array`
* `drawImageDithered(...)` — needs `ext-gd`

### Buffers

* `Microscrap\GFX\PhpdaFruit\Buffers\PageSegmentBuffer` — U8G2-style 8-row page buffer; `dump()` emits one partial update per dirty page.
* `Microscrap\GFX\PhpdaFruit\Buffers\DirtyRegionsBuffer` — tracks and coalesces changed rectangles; `dump()` emits one partial update per region.

Both expose `markAllDirty()` for a forced full repaint and can be built fluently:

```php
use Microscrap\GFX\PhpdaFruit\Buffers\PageSegmentBuffer;
use ScrapyardIO\NutsAndBolts\Enums\BitDepth;
use ScrapyardIO\NutsAndBolts\Enums\BitOrder;
use ScrapyardIO\NutsAndBolts\Enums\PixelFormat;

$buffer = PageSegmentBuffer::size(128, 64)
    ->pixelFormat(PixelFormat::MONO_VERTICAL_PAGE)
    ->bitDepth(BitDepth::B1)
    ->bitOrder(BitOrder::LSB_FIRST)
    ->build();
```

## Testing (Pest v4)

```bash
./vendor/bin/pest
```

With coverage (requires Xdebug or PCOV):

```bash
XDEBUG_MODE=coverage ./vendor/bin/pest --coverage
```

* **75 tests / 165 assertions passing.**
* **64.1% line coverage** of behavioral code. The static glyph-data classes under `src/Fonts` are excluded from the metric via `phpunit.xml` since they carry no logic.
* Covered: both partial-refresh buffers and their factories, the renderer core (pixels, segments, clipping, rotation), every geometry primitive, classic-font text, bitmaps, color maps, Floyd–Steinberg dithering, and the GD image path.

## License

MIT. See [LICENSE.md](LICENSE.md).
