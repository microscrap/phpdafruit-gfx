---
okf_version: "0.2"
---

# microscrap/phpdafruit-gfx Knowledge Bundle

AdafruitGFX-like software Renderer2D for ScrapyardIO tubes 0.7.

**Trust rule:** Prefer `status: stable`. New agent-written concepts stay `status: draft` until a human verifies them.
**Placement:** Package root `.okf/` only.
**Dist note:** `.okf/` and `AGENTS.md` are `export-ignore`.

# Orientation

* [Package (0.7)](orientation/package.md) - Composer identity and role vs engine gfx companions.

# Core

* [PhpdafruitRenderer2D](core/renderer.md) - Managed-only Adafruit software renderer.

# Traps

* [Fabricate leftovers](traps/fabricate-leftovers.md) - Do not reintroduce fabricate/* ^0.6 deps or Fabricate Renderer2D.
