<?php

namespace DeptOfScrapyardRobotics\Displays\JD79661;

use GeneralPurposeIO\Contracts\IntegratedCircuits\CircuitException;
use GeneralPurposeIO\Contracts\IntegratedCircuits\RefreshMode;

class JD79661Exception extends CircuitException
{
    public static function invalidRegisterValue(string $field, int $value, int $min, int $max): static
    {
        return new static("JD79661 {$field} takes {$min} to {$max}; got {$value}.");
    }

    public static function invalidProperty(string $name, string $class): static
    {
        return new static("Invalid property '{$name}' on {$class}.");
    }

    public static function invalidGeometry(int $width, int $height): static
    {
        return new static("JD79661 panel size {$width} × {$height} does not fit the resolution register: width 1 to 65528, height 1 to 65535.");
    }

    public static function notConnected(string $protocol, string $driver, string|int $device): static
    {
        return new static("JD79661 could not get a {$protocol} connection from driver [{$driver}] on device [{$device}].");
    }

    public static function incompletePin(string $name): static
    {
        return new static("JD79661 needs its {$name} pin as driver, device and pin.");
    }

    public static function invalidSpiMode(int $mode): static
    {
        return new static("SPI mode {$mode} does not exist; use 0 to 3.");
    }

    public static function invalidSpiClock(int $hz): static
    {
        return new static("JD79661 SPI clock {$hz} Hz must be at least 1 Hz.");
    }

    public static function wrongSpiMode(string|int $device, int $bus_mode, int $mode): static
    {
        return new static("SPI bus [{$device}] runs in mode {$bus_mode}; this panel is configured for mode {$mode}.");
    }

    public static function spiWriteFailed(string $what, int $expected, int $written): static
    {
        return new static("JD79661 SPI {$what} write failed: {$written} of {$expected} bytes. Check wiring, SPI bus permissions, and that the panel is powered.");
    }

    public static function invalidPacketSize(int $size): static
    {
        return new static("JD79661 max_packet_size {$size} must be at least 1.");
    }

    public static function busyTimeout(int $timeout_ms): static
    {
        return new static("JD79661 BUSY stayed low for {$timeout_ms} ms. Check the BUSY pin and the panel's power.");
    }

    public static function wholeFrameOnly(int $x, int $y, int $width, int $height, int $panel_width, int $panel_height): static
    {
        return new static("JD79661 takes whole frames: transmit() needs (0, 0) and {$panel_width} × {$panel_height}; got {$width} × {$height} at ({$x}, {$y}).");
    }

    public static function wrongByteCount(int $expected, int $got): static
    {
        return new static("JD79661 frame needs {$expected} bytes; got {$got}.");
    }

    public static function unsupportedRefreshMode(RefreshMode $mode): static
    {
        return new static("JD79661 has no {$mode->value} refresh.");
    }
}
