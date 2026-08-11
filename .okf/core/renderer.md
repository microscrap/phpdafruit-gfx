---
type: Core
title: PhpdafruitRenderer2D
description: AdafruitGFX-style tubes Renderer2D — Managed framebuffer only; rotation, clip, primitives, text.
tags: [core, renderer, adafruit, managed]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-10T22:20:00Z" }
status: draft
sources:
  - id: renderer
    resource: src/PhpdafruitRenderer2D.php
    title: PhpdafruitRenderer2D
  - id: api
    resource: src/Concerns/GFXAPI.php
    title: GFXAPI concern bundle
  - id: deprecated
    resource: src/PhpdafruitGfx.php
    title: Deprecated PhpdafruitGfx alias
---

# Role

`PhpdafruitRenderer2D` extends tubes `Renderer2D` and composes Adafruit-style concern traits (lines, rects, rounds, triangles, bitmaps, images, dithering, rotation, local clip). Text uses tubes `DrawsText`.

# Framebuffer rules

- `setFramebuffer` / construct require a tubes **ManagedFramebuffer**.
- Deferred / engine buffers are rejected — use the engine’s Renderer2D for those.
- `preferredFramebuffer(FormatSpec, w, h)` picks:
  - `MONO_VERTICAL_PAGE` → `PageSegmentBuffer`
  - `ROW_MAJOR` → `DirtyRegionsBuffer`
  - else → `FullFramebuffer`

# Present path (PanelIC)

```text
PhpdafruitRenderer2D → Managed FB → PanelIC::present → flush → IC.transmit
```

`render()` flushes the bound buffer host FormatSpec to `DumpedBuffer[]` (legacy helper; PanelIC usually calls `flush` itself on present).

# Deprecated

`PhpdafruitGfx` extends `PhpdafruitRenderer2D` for old call sites — prefer the new class name.
