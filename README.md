Introduction
============

PHP Package for the JD79661 four-color ePaper (electronic ink) display
controller. The JD79661 drives black/white/red/yellow panels, packing all four
colors into a single RAM at 2 bits per pixel.

Compatible SPI Interfaces
===============
The JD79661 display communicates with your device over SPI, the Serial Peripheral Interface.

You can interface with displays such as the JD79661 with this package the following ways:
* A Linux Single-Board Computer's exposed GPIO pins using the dedicated SPI MOSI/SCK and CS pins as well as GPIO pins for DC, RST and BUSY.
* An MPSSE-enabled USB-to-Serial device such as an FT232H generally using D0 and SCK, D1 for MOSI, D2 for MISO and D3 for CS, plus GPIO for RST/DC/BUSY and connected to nearly any Linux or MacOS USB port.

A BUSY input is required, the same as DC and RST. A full four-color refresh
takes several seconds, and the driver blocks on the BUSY line to know when the
panel has finished the update.

Dependencies
=============
This package makes use of modules within:
* [The ScrapyardIO Framework](https://github.com/ScrapyardIO/framework)

This package also requires one of the following extensions in order to interface with SPI
* [POSI Extension v^0.4.0 or newer](https://github.com/php-io-extensions/posi)
* [FTDI Extension v^0.4.0 or newer](https://github.com/php-io-extensions/ftdi)

In addition, an extension wrapper package is needed

For ext-posi
* [Microscrap POSIX Package v0.4.0 or newer](https://github.com/microscrap/posix)
* [Microscrap Native SPI Package v0.4.0 or newer](https://github.com/microscrap/spi)
* [Microscrap Native GPIO Package v0.4.0 or newer](https://github.com/microscrap/gpio)

For ext-ftdi
* [Microscrap FTDI Package v0.4.0 or newer](https://github.com/microscrap/ftdi)
* [Microscrap MPSSE Package v0.4.0 or newer](https://github.com/microscrap/mpsse)

Installing from Composer
====================
Inside the root of your PHP Project, simply require the JD79661 package from composer
```shell
composer require dept-of-scrapyard-robotics/jd79661
```
Framework Configuration
====================
If you would like to use the ScrapyardIO Framework to bootstrap your display without
wasting lines configuring your display right in the script you can add your desired
configuration to scrapyard-io.php, such as in this example:

### SPI
```php

use DeptOfScrapyardRobotics\Displays\JD79661\JD79661\JD79661;

return [
    'displays' => [
        // For Native Configurations 
        'jd79661-native' => [
            'class_name' => JD79661::class,
            'connection' => ['driver' => 'native'],
            'startup' => [
                'spi' => [0, 0],
                'gpiochip' => [0],
                'rst' => [24],
                'dc' => [22],
                'busy' => [17],
            ],
        ],
        // For USB Configurations
        'jd79661-usb' => [
            'class_name' => JD79661::class,
            'connection' => ['driver' => 'usb'],
            'startup' => [
                'spi' => ['ft232h', 0],
                'gpiochip' => ['ft232h'],
                'rst' => [0],
                'dc' => [1],
                'busy' => [2],
            ],
        ],        
    ]
];
```

Basic Usage
============

### Native (POSIX) SPI driver. (Single Board Computers)
```php

use DeptOfScrapyardRobotics\Displays\JD79661\JD79661\JD79661;

$native_spi_display = JD79661::connection('native')
    ->spi(0, 0)
    ->gpiochip(0)
    ->rst(24)
    ->dc(22)
    ->busy(17)
    ->create()
```

### USB (MPSSE) driver using SPI. (Linux and MacOS)
```php

use DeptOfScrapyardRobotics\Displays\JD79661\JD79661\JD79661;

$usb_spi_display = JD79661::connection('usb')
    ->spi('ft232h', 0)
    ->gpiochip('ft232h')
    ->rst(0)
    ->dc(1)
    ->busy(2)
    ->create()
```

## Alternative Usage

### Using Through the Display Library (as a QuadColorePaperDisplay)
```php
use DeptOfScrapyardRobotics\Displays\JD79661\JD79661\JD79661;
use RealityInterface\Displays\Applied\ePaper\QuadColorePaperDisplay;

$jd79661 = JD79661::connection('usb')
    ->spi('ft232h', 0)
    ->gpiochip('ft232h')
    ->rst(0)
    ->dc(1)
    ->busy(2)
    ->create()
    
$display = QuadColorePaperDisplay::as($jd79661);

```

### Using Through the Display Framework (with an autoloaded config) (as a QuadColorePaperDisplay)
```php

use RealityInterface\Displays\Applied\ePaper\QuadColorePaperDisplay;

$display = QuadColorePaperDisplay::using('jd79661-usb');

```

Display API
==========
The setters in this API interface with the device directly (register writes), so
you can use property access while still working against the panel itself.

Readable Properties (Getters)
-----------------------------
There are no readable magic properties exposed for the JD79661 in this package.

Writable Properties (Setters)
-----------------------------
* `$display->panel_setting = new JD79661PanelSetting(...);`
  Sets the panel setting register (PSR).

* `$display->power_setting = new JD79661PowerSetting(...);`
  Sets the power setting register (PWR).

* `$display->power_off_sequence = new JD79661PowerOffSequence(...);`
  Sets the power-off sequence (POFS).

* `$display->booster_soft_start = new JD79661BoosterSoftStart(...);`
  Sets the booster soft-start configuration (BTST).

* `$display->vcom_data_interval = new JD79661VcomDataInterval(...);`
  Sets the VCOM and data interval (CDI).

* `$display->tcon = new JD79661TCON(...);`
  Sets the gate/source non-overlap timing (TCON).

* `$display->pll_control = new JD79661PLLControl(...);`
  Sets the PLL clock frequency (PLL).

* `$display->power = true;`
  Powers the panel on (`true`) or off (`false`).

Drawing on the Display
============
Draw with a `Screen`, which wraps a `GFXRenderer` over a `ChannelSortedFrameBuffer`
matched to the panel's `FormatSpec`, then ships the bytes on `render()`. ePaper is
a single slow full refresh, so paint one complete composition and render once.

Colors are `EInkColor` cases: `WHITE`, `BLACK`, `RED` and `YELLOW`. The driver
merges them into the panel's single 2bpp RAM at transmit time.

```php
use DeptOfScrapyardRobotics\Displays\JD79661\JD79661\JD79661;
use Microscrap\GFX\PhpdaFruit\GFXRenderer;
use RealityInterface\Displays\Applied\ePaper\Buffers\ChannelSortedFrameBuffer;
use RealityInterface\Displays\Applied\ePaper\Enums\EInkColor;
use RealityInterface\Displays\Applied\ePaper\QuadColorePaperDisplay;
use RealityInterface\Displays\Screen;

$jd79661 = JD79661::connection('usb')
    ->spi('ft232h', 0)
    ->gpiochip('ft232h')
    ->rst(0)
    ->dc(1)
    ->busy(2)
    ->create();

$display = QuadColorePaperDisplay::as($jd79661);

$buffer = new ChannelSortedFrameBuffer($display->width(), $display->height(), $display->getFormatSpec());
$screen = new Screen($display, new GFXRenderer($buffer));

$screen
    ->fill(EInkColor::WHITE->value)
    ->drawRect(0, 0, $display->width(), $display->height(), EInkColor::BLACK->value)
    ->fillCircle(intdiv($display->width(), 2), 40, 18, EInkColor::RED->value)
    ->setTextColor(EInkColor::BLACK->value)
    ->setCursor(8, 80)
    ->print('JD79661')
    ->setTextColor(EInkColor::YELLOW->value)
    ->setCursor(8, 100)
    ->print('B/W/R/Y')
    ->render();
```
