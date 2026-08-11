---
type: Orientation
title: Package (0.7)
description: microscrap/phpdafruit-gfx — Adafruit software Renderer2D for tubes Managed framebuffers.
tags: [orientation, package, 0.7]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-10T22:20:00Z" }
status: draft
sources:
  - id: composer
    resource: composer.json
    title: Package composer.json
---

# Identity

| Field | Value |
|-------|--------|
| Composer | `microscrap/phpdafruit-gfx` `0.7.0` |
| PHP | `^8.4\|^8.5\|^8.6` |
| Requires | `scrapyard-io/tubes ^0.7.0` |
| Namespace | `Microscrap\GFX\PhpdaFruit\` |

# Role

CPU Adafruit-style drawing into tubes **Managed** buffers. Complements engine Deferred gfx packages (`metal-gfx`, `sdl3-gfx`, …) which own GPU framebuffers + their Renderer2D.

| Canvas | Typical pairing |
|--------|-----------------|
| PanelIC (CPU) | Managed FB + `PhpdafruitRenderer2D` |
| PanelIC (GPU) | Engine Deferred + engine Renderer2D |
| OSWindow | Engine Deferred + engine Renderer2D (not this package) |

# Related

- [PhpdafruitRenderer2D](../core/renderer.md)
- Tubes OKF: Panel factory / Rendering
