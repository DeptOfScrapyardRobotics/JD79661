<?php

namespace DeptOfScrapyardRobotics\Displays\JD79661\Concerns;

use DeptOfScrapyardRobotics\Displays\JD79661\JD79661Configuration;
use DeptOfScrapyardRobotics\Displays\JD79661\JD79661Exception;
use DeptOfScrapyardRobotics\Displays\JD79661\Transports\JD79661SPITransport;
use GeneralPurposeIO\Contracts\Digital\DigitalInTransport;
use GeneralPurposeIO\Contracts\Digital\DigitalOutTransport;
use GeneralPurposeIO\Contracts\SPI\SPIMode;
use Voyager\Vessel\ControlPanel;

/**
 * The spi() protocol factory the circuit catalog calls. Its parameters are the keys of a config/circuits/jd79661.php
 * entry, so app('circuit')->conjure('jd79661') builds a wired, booted panel from the app's config alone. A bus or
 * pin device that is not connected yet is connected here; one the app already connected is shared as it is.
 */
trait ConjuresJD79661
{
    /**
     * Opens the bus in $mode when it is not connected yet and clocks this chip select at $speed whatever the bus
     * runs at; a bus the app already opened in another mode is refused. DC, RST and BUSY are opened after the bus,
     * so pins on an FT232H ride the bus's own context.
     *
     * @param  array{driver: string, device: string|int, pin: int}  $dc
     * @param  array{driver: string, device: string|int, pin: int}  $rst
     * @param  array{driver: string, device: string|int, pin: int}  $busy
     */
    public static function spi(
        string $driver,
        string|int $device,
        array $dc,
        array $rst,
        array $busy,
        int $chip_select = 0,
        int $mode = 0,
        int $speed = 10_000_000,
        ?int $width = null,
        ?int $height = null,
        bool $boot_now = true,
    ): static {
        $spi_mode = SPIMode::tryFrom($mode) ?? throw JD79661Exception::invalidSpiMode($mode);

        if ($speed < 1) {
            throw JD79661Exception::invalidSpiClock($speed);
        }

        $configuration = new JD79661Configuration(...array_filter(['width' => $width, 'height' => $height], static fn (?int $value): bool => ! is_null($value)));

        $bus = static::gpio('gpio.spi')->driver($driver);
        $spi = $bus->device($device, $chip_select)
            ?? $bus->connectTo($device)->mode($spi_mode)->speed($speed)->register()->device($device, $chip_select);

        if (is_null($spi)) {
            throw JD79661Exception::notConnected('SPI', $driver, $device);
        }

        $bus_mode = $bus->settingsOf($device)?->mode;

        if (! is_null($bus_mode) && $bus_mode !== $spi_mode) {
            throw JD79661Exception::wrongSpiMode($device, $bus_mode->value, $mode);
        }

        $spi->speed($speed);

        $transport = new JD79661SPITransport($spi, static::outputLine($dc, 'dc'), static::outputLine($rst, 'rst'), static::inputLine($busy, 'busy'));

        return new static($transport, $configuration, $boot_now);
    }

    /** @param  array{driver?: string, device?: string|int, pin?: int}  $line */
    protected static function outputLine(array $line, string $name): DigitalOutTransport
    {
        if (! isset($line['driver'], $line['device'], $line['pin'])) {
            throw JD79661Exception::incompletePin($name);
        }

        $pins = static::gpio('gpio.digital')->driver($line['driver']);
        $pin = $pins->output($line['device'], $line['pin'])
            ?? $pins->connectTo($line['device'])->register()->output($line['device'], $line['pin']);

        return $pin ?? throw JD79661Exception::notConnected('DigitalIO', $line['driver'], $line['device']);
    }

    /** @param  array{driver?: string, device?: string|int, pin?: int}  $line */
    protected static function inputLine(array $line, string $name): DigitalInTransport
    {
        if (! isset($line['driver'], $line['device'], $line['pin'])) {
            throw JD79661Exception::incompletePin($name);
        }

        $pins = static::gpio('gpio.digital')->driver($line['driver']);
        $pin = $pins->input($line['device'], $line['pin'])
            ?? $pins->connectTo($line['device'])->register()->input($line['device'], $line['pin']);

        return $pin ?? throw JD79661Exception::notConnected('DigitalIO', $line['driver'], $line['device']);
    }

    /** A protocol manager from the app's container: gpio.spi or gpio.digital. */
    protected static function gpio(string $manager): mixed
    {
        return ControlPanel::getInstance()->make($manager);
    }
}
