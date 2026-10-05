---
type: Guide
title: Drawing and refreshing
description: The two-bit FormatSpec, row padding, transmit(), refresh(), sleep, timings.
tags: [drawing, formatspec, transmit, refresh, epaper]
status: draft
generated: { by: claude-opus/5.5, at: 2026-10-05T04:30:00Z }
sources:
  - id: panel
    resource: src/JD79661.php
    title: JD79661
  - id: ink
    resource: src/Enums/JD79661Ink.php
    title: JD79661Ink
---

# FormatSpec

`ROW_MAJOR`, `B2`, `MSB_FIRST`, palette with codes: BLACK 00, WHITE 01, YELLOW 10, RED 11.[^ink] Four pixels a byte, leftmost in bits 7:6, rows padded to a byte. Surface's `epaper()` framebuffer in this spec stores exactly that and starts as paper (`55`); checked: `K R Y W` → `39`.

# transmit()

Whole frames only: origin (0, 0), width × height, else `wholeFrameOnly`.[^panel] Bytes = ceil(w / 4) × h. The controller's rows span the width rounded up to 8 pixels (TRES), so 122 → 31-byte rows padded with `55` (white) to 32. `10` + frame.

# refresh() / sleep()

`refresh()` → `12 00`, BUSY. PARTIAL → `unsupportedRefreshMode`. `sleep()` → `02 00`, BUSY, `07 A5`; next `boot()` resets.

# Live reference

FT232H, 10 MHz, 122 × 250: transmit 13 ms (7750 → 8000 bytes), full refresh 20.2 s; black border and X, black, red and yellow squares as drawn.

[^panel]: JD79661
[^ink]: JD79661Ink
