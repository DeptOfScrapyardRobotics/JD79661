---
okf_version: "0.2"
---

# dept-of-scrapyard-robotics/jd79661

JD79661 four-colour (black, white, yellow, red) ePaper driver for `scrapyard-io/framework` 0.10, over SPI with DC, RST and BUSY. Conjured from config, whole frames of two-bit palette codes, full refresh.

Read this index first, open only concepts task needs. Every concept `status: draft` until human verifies.

# Concepts

* [Package](overview.md) - JD79661 ePaper driver for scrapyard-io/framework 0.10 — identity, requires, boot, errors.
* [Connecting](connecting.md) - conjure() and the spi() factory, DC / RST / BUSY, the FT232H bench, building the transport by hand.
* [Drawing and refreshing](drawing.md) - the two-bit FormatSpec, row padding, transmit(), refresh(), sleep, timings.
* [Configuration](configuration.md) - JD79661Configuration, register breakouts, vendor registers, config keys.

# Runbooks

* [Hardware smoke](runbooks/hardware-smoke.md) - four-colour frame on the FT232H bench.

# Log

* [log.md](log.md)
