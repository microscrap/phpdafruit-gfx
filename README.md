# microscrap/phpdafruit-gfx

AdafruitGFX-style software `Renderer2D` for ScrapyardIO **tubes 0.7**.

Draws 2D primitives into tubes **Managed** framebuffers (`full` / `dirty` / `page`). Use it for PanelIC CPU rendering (SSD1306, ST77xx, …). For GPU PanelIC paths, use the matching engine companion (`metal-gfx`, `sdl3-gfx`, …) with `Panel::make()->wrap($ic)->useFramebuffer($engineFb)`.

Docs: [ecosystem phpdafruit-gfx 0.7.x](https://scrapyard-io.projectsaturnstudios.com/ecosystem/microscrap/phpdafruit-gfx/0.7.x)

## Require

```bash
composer require microscrap/phpdafruit-gfx:^0.7.0
```

## Smoke

```php
use Microscrap\GFX\PhpdaFruit\PhpdafruitRenderer2D;
use ScrapyardIO\Tubes\Contracts\Framebuffers\Enums\BitDepth;
use ScrapyardIO\Tubes\Contracts\Framebuffers\Enums\PixelFormat;
use ScrapyardIO\Tubes\Contracts\Framebuffers\FormatSpec;

$spec = new FormatSpec(PixelFormat::ROW_MAJOR, BitDepth::B16);
$fb = PhpdafruitRenderer2D::preferredFramebuffer($spec, 128, 64);
$gfx = new PhpdafruitRenderer2D($fb);

$gfx->fillScreen(0x0000);
$gfx->drawRect(2, 2, 124, 60, 0xFFFF);
$frames = $gfx->render(); // DumpedBuffer[] for PanelIC::present / IC transmit
```

## License

MIT
