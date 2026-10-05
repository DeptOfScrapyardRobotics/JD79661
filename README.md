# jd79661

[![Latest Version on Packagist](https://img.shields.io/packagist/v/dept-of-scrapyard-robotics/jd79661.svg)](https://packagist.org/packages/dept-of-scrapyard-robotics/jd79661)
[![License](https://img.shields.io/packagist/l/dept-of-scrapyard-robotics/jd79661.svg)](LICENSE)

Drive JD79661 four-colour ePaper panels (black, white, yellow, red) from PHP over SPI, using the ScrapyardIO GPIO framework.

`dept-of-scrapyard-robotics/jd79661` boots the controller with its reference bring-up, takes whole frames packed as two-bit colour codes, and refreshes the panel. A Surface ePaper framebuffer created with the panel's `formatSpec()` stores frames in exactly that packing.

```
ext-posi / ext-ftdi            1:1 system and libftdi calls
  → microscrap/*               libgpiod, spidev, libmpsse in PHP
    → microscrap/scrapyard-*   adapters: the `native` and `usb` drivers
      → scrapyard-io/framework protocol managers, transports, the circuit catalog
        → dept-of-scrapyard-robotics/jd79661   ← this package
```

## Requirements

- PHP 8.4 or newer
- A Venusian 0.10 application with the `scrapyard-io/framework` 0.10 components (`gpio/spi`, `gpio/digital`, `gpio/integrated-circuits`)
- `venusian-surface/contracts` 0.10, for the `FormatSpec` the panel describes its bytes with
- An adapter: `microscrap/scrapyard-linux` (driver `native`, needs `ext-posi`) or `microscrap/scrapyard-usb` (driver `usb`, FTDI MPSSE boards such as the FT232H, needs `ext-ftdi`)
- `venusian-surface/framebuffers` 0.10 if you want Surface to pack your frames

## Installation

```bash
composer require dept-of-scrapyard-robotics/jd79661
```

The service provider is discovered automatically. It merges the wiring config under `circuits.jd79661` and registers the panel with the circuit catalog. To publish the config, run:

```bash
php computer vendor:publish --tag=jd79661-config
```

## Quick start

A 122 × 250 panel on an FT232H: chip select on GPIO0 (D4), DC on GPIO1 (D5), RST on GPIO2 (D6), BUSY on GPIO3 (D7).

```php
// config/circuits/jd79661.php
return [
    'default_config' => 'spi',
    'configs' => [
        'spi' => [
            'driver' => 'usb',
            'device' => 'ft232h',
            'chip_select' => 0,
            'dc' => ['driver' => 'usb', 'device' => 'ft232h', 'pin' => 1],
            'rst' => ['driver' => 'usb', 'device' => 'ft232h', 'pin' => 2],
            'busy' => ['driver' => 'usb', 'device' => 'ft232h', 'pin' => 3],
        ],
    ],
];
```

```php
use Surface\Framebuffers\Native\NativeFramebufferDriver;

$panel = app('circuit')->conjure('jd79661');   // reset and booted

$spec = $panel->formatSpec();
$fb = (new NativeFramebufferDriver)->epaper($spec, $panel->width(), $panel->height());   // starts white
$fb->writeRgba8($rgba, $panel->width(), $panel->height());   // black, white, red and yellow pixels

$panel->transmit(0, 0, $fb->flush($spec, true));
$panel->refresh();   // about 20 seconds
```

Booting takes about 300 ms. A full refresh takes about 20 seconds.

## Connecting

`conjure('jd79661')` reads `circuits.jd79661`, picks `default_config`, and calls `JD79661::spi()` with that entry's keys. You can call it directly:

```php
use DeptOfScrapyardRobotics\Displays\JD79661\JD79661;

$panel = JD79661::spi(
    'usb', 'ft232h',
    dc: ['driver' => 'usb', 'device' => 'ft232h', 'pin' => 1],
    rst: ['driver' => 'usb', 'device' => 'ft232h', 'pin' => 2],
    busy: ['driver' => 'usb', 'device' => 'ft232h', 'pin' => 3],
);
```

A bus or pin device that isn't connected yet is connected by the factory; one your app already connected is shared. The factory opens an unconnected bus in `mode` (0 by default), refuses a bus already open in a different mode, and clocks this chip select at `speed` (10 MHz by default). DC, RST and BUSY are opened after the bus, so on an FT232H they ride the same USB context. Pass `width` and `height` for a panel other than 122 × 250, and `boot_now: false` to build without booting.

### Building the transport yourself

```php
use DeptOfScrapyardRobotics\Displays\JD79661\Transports\JD79661SPITransport;

$spi = app('gpio.spi')->driver('usb')->connectTo('ft232h')->mode(0)->speed(10_000_000)->register()->device('ft232h', 0);
$pins = app('gpio.digital')->driver('usb');

$panel = new JD79661(new JD79661SPITransport($spi, $pins->output('ft232h', 1), $pins->output('ft232h', 2), $pins->input('ft232h', 3)), boot_now: true);
```

## Drawing

The panel's RAM holds two bits a pixel, four pixels to a byte with the leftmost in the top two bits, rows padded to a whole byte. The colour codes are black `00`, white `01`, yellow `10`, red `11` (`JD79661Ink`).

`transmit()` takes whole frames only: origin (0, 0) and the full width and height. The controller's rows span the width rounded up to 8 pixels, so on a 122-pixel panel each 31-byte row is padded with white to 32. The panel doesn't change until `refresh()`.

## Refreshing and sleep

```php
$panel->refresh();   // full refresh; blocks until the panel raises BUSY
$panel->sleep();     // power off, then deep sleep; the image stays
$panel->boot();      // wakes it with a hardware reset
```

The JD79661 has no partial refresh: `refresh(RefreshMode::PARTIAL)` throws. BUSY reads low while the panel works; if it stays low past `busy_timeout_ms` (60 s by default) the call throws.

## Configuration object

`JD79661Configuration` holds the geometry and the values boot writes; the defaults are the 2.13 inch module's reference bring-up.

| Argument | Default |
|---|---|
| `width`, `height` | `122`, `250` |
| `panel_setting` | `0x8F 0x29` |
| `power_setting` | `0x07 0x00` |
| `power_off_sequence` | `0x10 0x54 0x44` |
| `booster_soft_start` | `0x05 0x00 0x3F 0x0A 0x25 0x12 0x1A` |
| `vcom_data_interval` | `0x37` |
| `tcon` | `0x02 0x02` |
| `pll_control` | `0x08` |
| `max_packet_size` | `4096` |
| `busy_timeout_ms` | `60000` |

Each register is a breakout in `Breakouts\` that checks every byte. Any configuration key reads as a property, and the seven register keys can be written, reaching the chip straight away. The vendor registers the bring-up also writes (`0x4D`, `0xE7`, `0xE3`, `0xB4`, `0xB5`, `0xE9`) have fixed values in `JD79661VendorRegister`.

## Errors

Everything throws `JD79661Exception`, which descends from `GeneralPurposeIO\Contracts\IntegratedCircuits\CircuitException`: BUSY timeouts, failed writes, `transmit()` with anything but a whole frame or the wrong byte count, a partial refresh, register values out of range, a size the resolution register can't hold, unknown properties, and SPI or pin settings it can't use. SPI has no acknowledge, so a missing panel shows up as a BUSY timeout.

## Closing

`close()` releases DC, RST and BUSY. The SPI connection stays with its driver, and the panel keeps its image.

## Configuration file

`config/circuits/jd79661.php`: `default_config` (`'spi'`); `configs.spi.driver`, `device`, `chip_select` (0), `mode` (0), `speed` (10 MHz), `width` / `height` (null keeps 122 × 250), `dc` / `rst` / `busy` (`driver`, `device`, `pin`; pins 1 / 2 / 3), and `boot_now` (true).

## Testing

```bash
composer install
vendor/bin/pest
```

The suite runs against recording fakes of the SPI bus, the pins and a scripted BUSY line, so it needs no hardware. A 122 × 250 panel on an FT232H was also exercised for this release, with someone watching: one frame with a black border and X and black, red and yellow squares, shown after a 20-second full refresh.

## Security

The driver writes commands and image data to hardware the PHP process can open. See [SECURITY.md](SECURITY.md).

## License

MIT. See [LICENSE](LICENSE).
