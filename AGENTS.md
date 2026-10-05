# Agent guidelines — dept-of-scrapyard-robotics/jd79661

## Knowledge Bundle (OKF)

This package ships an Open Knowledge Format bundle at [`.okf/`](.okf/) (excluded from the Composer dist via `.gitattributes` `export-ignore`). Before changing code or advising on this package: read [`.okf/index.md`](.okf/index.md) first, open only the concepts the task needs, prefer `status: stable` over `draft`. When you learn something durable, update the affected concept(s), bump `generated.at`, and append [`.okf/log.md`](.okf/log.md); new or changed concepts stay `status: draft` until a human verifies them. The bundle documents the package, never a session.

Do **not** create `.okf` folders under `src/*`. Catalog, transport and adapter semantics belong to `scrapyard-io/framework`'s bundle, framebuffer packing to Surface's.

## Package rules (quick) — 0.10.x

- Composer: `dept-of-scrapyard-robotics/jd79661` **0.10.0**. PHP `^8.4|^8.5|^8.6`. Namespace `DeptOfScrapyardRobotics\Displays\JD79661\` → `src/`.
- **Requires split components only**: `gpio/contracts`, `gpio/integrated-circuits`, `venusian-surface/contracts`, `venusian-voyager/nuts-and-bolts`, `venusian-voyager/vessel`. Never `scrapyard-io/framework`, `venusian/surface` or `venusian/framework`.
- **Panel = `Bootable` + `DisplayPanel` + `RefreshesOnCommand`.** Not `WindowAddressable` (whole frames only), not `Switchable`, full refresh only. Keep the three `formatSpec` methods as plain methods (Surface 0.10 has no `FormatSpecification`).
- **Boot** is the reference bring-up in its order, magic init (`0x4D`) first; vendor registers carry fixed payloads in `JD79661VendorRegister`. BUSY is active low.
- **Frames** are two-bit palette codes (`JD79661Ink`), rows padded with white from ceil(w/4) bytes to the controller's width rounded up to 8 pixels.
- **The factory is the config shape.** `ConjuresJD79661::spi()` parameters are exactly a `circuits.jd79661.configs.*` entry's keys. Bus first, then DC, RST, BUSY.
- **Every write is checked; every BUSY wait has a timeout.** Breakouts range-check, never mask.
- Enums int-backed, FULLY UPPERCASE cases. No class constants. `is_null($x)` over `$x === null`.

## Verification

```bash
vendor/bin/pest            # recording fake bus, pins and scripted BUSY; no hardware
```

Run it under NTS and ZTS PHP before every commit. Suites stay hardware-free. Hardware truth: a 122 × 250 panel on an FT232H (CS D4, DC D5, RST D6, BUSY D7), proven by watching a four-colour frame.
