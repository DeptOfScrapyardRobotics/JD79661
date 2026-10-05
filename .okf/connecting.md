---
type: Guide
title: Connecting a JD79661
description: conjure() and the spi() factory, DC / RST / BUSY, the FT232H bench, building the transport by hand.
tags: [spi, transport, dc, rst, busy, conjure, ft232h]
status: draft
generated: { by: claude-opus/5.5, at: 2026-10-05T04:30:00Z }
sources:
  - id: factory
    resource: src/Concerns/ConjuresJD79661.php
    title: ConjuresJD79661
  - id: transport
    resource: src/Transports/JD79661SPITransport.php
    title: JD79661SPITransport
---

# spi()

`JD79661::spi(driver, device, dc, rst, busy, chip_select = 0, mode = 0, speed = 10_000_000, width = null, height = null, boot_now = true)`[^factory] — mode 0–3 and speed ≥ 1 checked before the bus; unconnected bus opened in `mode`, shared bus in another mode refused; chip select clocked at `speed`; DC, RST outputs and BUSY input after the bus. `conjure('jd79661')` passes `circuits.jd79661` keys.

Transport:[^transport] DC low + command byte, parameters DC high; data packets ≤ `max_packet_size`, each write checked; BUSY low = busy, read every 1 ms up to `busy_timeout_ms`.

# Bench

FT232H, driver `usb`: `chip_select` 0 (D4), DC pin 1 (D5), RST pin 2 (D6), BUSY pin 3 (D7).

# By hand

```php
$spi = app('gpio.spi')->driver('usb')->connectTo('ft232h')->mode(0)->speed(10_000_000)->register()->device('ft232h', 0);
$pins = app('gpio.digital')->driver('usb');
$panel = new JD79661(new JD79661SPITransport($spi, $pins->output('ft232h', 1), $pins->output('ft232h', 2), $pins->input('ft232h', 3)), boot_now: true);
```

[^factory]: ConjuresJD79661
[^transport]: JD79661SPITransport
