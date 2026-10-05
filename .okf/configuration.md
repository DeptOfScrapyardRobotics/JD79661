---
type: Configuration
title: Configuration
description: JD79661Configuration, register breakouts, vendor registers, circuits.jd79661 keys.
resource: config/jd79661.php
tags: [config, configuration, registers]
status: draft
generated: { by: claude-opus/5.5, at: 2026-10-05T04:30:00Z }
sources:
  - id: config
    resource: src/JD79661Configuration.php
    title: JD79661Configuration
  - id: vendor
    resource: src/Enums/JD79661VendorRegister.php
    title: JD79661VendorRegister
---

# JD79661Configuration

`width` 122, `height` 250 (TRES 16-bit), `panel_setting` (8F 29), `power_setting` (07 00), `power_off_sequence` (10 54 44), `booster_soft_start` (7 bytes), `vcom_data_interval` (37), `tcon` (02 02), `pll_control` (08), `max_packet_size` 4096, `busy_timeout_ms` 60 000 (refresh ≈ 20 s).[^config] Breakouts range-check every byte. Properties: any key reads; the seven register keys write chip then config.

Vendor registers (meaning unpublished) carry fixed payloads in `JD79661VendorRegister`:[^vendor] 4D 78, E7 1C, E3 22, B4 D0, B5 03, E9 01.

# Config file

`config/jd79661.php` → `circuits.jd79661`, tag `jd79661-config`, catalog `jd79661`. Keys: `default_config` 'spi'; `driver`, `device`, `chip_select`, `mode`, `speed`, `width`, `height`, `dc` / `rst` / `busy` `{driver, device, pin}` (pins 1 / 2 / 3), `boot_now`.

[^config]: JD79661Configuration
[^vendor]: JD79661VendorRegister
