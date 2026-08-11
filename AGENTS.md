# Agent guidelines — microscrap/phpdafruit-gfx

## Knowledge Bundle (OKF)

This package ships an Open Knowledge Format bundle at [`.okf/`](.okf/) (excluded from Composer dist via `.gitattributes` `export-ignore`).

Before changing package code:

1. Read [`.okf/index.md`](.okf/index.md) first.
2. Open only the linked concepts needed for the task.
3. Prefer `status: stable`; treat `deprecated` as historical. New/changed concepts stay `status: draft` until a human verifies them.
4. When you learn something durable about **this package**, update the affected `.okf` concept(s) and append `.okf/log.md`.
5. Keep `.okf` at the **package root** only.

## Package rules (quick) — 0.7.x

- Composer: `microscrap/phpdafruit-gfx` **0.7.0**. PHP `^8.4|^8.5|^8.6`. Requires `scrapyard-io/tubes ^0.7.0`.
- Entry point: `PhpdafruitRenderer2D` (Adafruit-style software `Renderer2D`). `PhpdafruitGfx` is a deprecated thin subclass.
- Draws into tubes **Managed** framebuffers only (not Deferred). Pair with PanelIC CPU path; GPU PanelIC uses engine Renderer2D + Deferred instead.
- Prefer `is_null($var)`; backed enums FULLY UPPERCASE; no class-level constants.
- Tubes / PanelIC / Window architecture lives in `scrapyard-io/tubes` OKF — do not duplicate it here.
