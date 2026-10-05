---
type: Runbook
title: Hardware smoke
description: One four-colour frame on the FT232H bench, through the real providers and a Surface ePaper framebuffer.
tags: [hardware, smoke, ft232h, epaper]
status: draft
generated: { by: claude-opus/5.5, at: 2026-10-05T04:30:00Z }
---

# Rule

Suite stays hardware-free; panels proven by scratch scripts outside the repo, someone watching.

# Bench

FT232H, driver `usb`: CS D4 (`chip_select` 0), DC pin 1, RST pin 2, BUSY pin 3. Build ext-ftdi 0.10 in scratch if the system one is older; run `php -n -d extension=…/ftdi.so`.

# Scratch project

This package and Surface `contracts`, `nuts-and-bolts`, `framebuffers` by path repo, `microscrap/scrapyard-usb`, `gpio/digital`, `gpio/spi`, `gpio/i2c`, `venusian-voyager/io-pools`, `venusian-voyager/config`. Boot the GPIO providers (no PWM: the USB adapter does not pull gpio/pwm), the adapter's and `JD79661ServiceProvider`, then `conjure('jd79661')`.

# Check

White frame with black border and X, black square top-left, red square centre, yellow square near the bottom; one full refresh (≈ 20 s); `sleep()`. Colours swapped → `JD79661Ink` codes.
