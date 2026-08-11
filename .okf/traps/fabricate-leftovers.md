---
type: Trap
title: Fabricate leftovers
description: Do not reintroduce fabricate/* 0.6 deps or Fabricate Renderer2D / buffer types.
tags: [trap, fabricate, migration]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-10T22:20:00Z" }
status: draft
---

# Trap

Pulling `fabricate/framebuffers`, `fabricate/rendering`, or `Fabricate\Rendering\Renderer2D` back into this package.

# Why it hurts

0.7 lives on `scrapyard-io/tubes` contracts. Fabricate 0.6 buffers and renderers will not bind to PanelIC / Window 0.7 surfaces.

# Correct shape

- Extend `ScrapyardIO\Tubes\Rendering\Renderer2D`
- Write tubes `ManagedFramebuffer` only
- Text via tubes `DrawsText` / `GFXFont`
