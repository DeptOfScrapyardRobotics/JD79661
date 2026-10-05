<?php

use DeptOfScrapyardRobotics\Displays\JD79661\Breakouts\JD79661PLLControl;
use DeptOfScrapyardRobotics\Displays\JD79661\Enums\JD79661Ink;
use DeptOfScrapyardRobotics\Displays\JD79661\JD79661;
use DeptOfScrapyardRobotics\Displays\JD79661\JD79661Configuration;
use DeptOfScrapyardRobotics\Displays\JD79661\JD79661Exception;
use GeneralPurposeIO\Contracts\IntegratedCircuits\CircuitException;
use GeneralPurposeIO\Contracts\IntegratedCircuits\DisplayPanel;
use GeneralPurposeIO\Contracts\IntegratedCircuits\RefreshesOnCommand;
use GeneralPurposeIO\Contracts\IntegratedCircuits\RefreshMode;
use GeneralPurposeIO\Contracts\IntegratedCircuits\Switchable;
use GeneralPurposeIO\Contracts\IntegratedCircuits\WindowAddressable;
use Surface\Contracts\Framebuffers\BitDepth;
use Surface\Contracts\Framebuffers\EInkColor;
use Surface\Contracts\Framebuffers\PixelFormat;

/** A whole 122 x 250 frame as Surface packs it: 31 bytes a row, every pixel $ink. */
function quadFrame(JD79661Ink $ink = JD79661Ink::WHITE): array
{
    $code = $ink->value;

    return array_fill(0, 31 * 250, ($code << 6) | ($code << 4) | ($code << 2) | $code);
}

it('is a panel that refreshes on command, takes whole frames, and does not switch', function (): void {
    expect(is_subclass_of(JD79661::class, DisplayPanel::class))->toBeTrue()
        ->and(is_subclass_of(JD79661::class, RefreshesOnCommand::class))->toBeTrue()
        ->and(is_subclass_of(JD79661::class, WindowAddressable::class))->toBeFalse()
        ->and(is_subclass_of(JD79661::class, Switchable::class))->toBeFalse();
});

it('boots with the reference bring-up: reset, wait, magic init, registers, power on', function (): void {
    [$panel, $spi, $pins] = quad();

    expect($panel->hasBooted())->toBeTrue()
        ->and($pins['rst']->levels)->toBe([true, false, true])
        ->and($spi->commands())->toBe(quadBootCommands())
        ->and([$panel->width(), $panel->height()])->toBe([122, 250]);
});

it('rounds the resolution\'s source count up to a byte of pixels', function (): void {
    [, $spi] = quad(new JD79661Configuration(width: 128, height: 296));

    expect($spi->commands()[7])->toBe([0x61, [0x00, 0x80, 0x01, 0x28]]);
});

it('waits while BUSY is low, after the reset and after power on', function (): void {
    [$panel, , $pins] = quad(boot: false);
    $pins['busy']->script = [false, false, true, false, true];

    $panel->boot();

    expect($pins['busy']->reads)->toBe(5);
});

it('throws when BUSY never rises', function (): void {
    [$panel, , $pins] = quad(new JD79661Configuration(busy_timeout_ms: 15), boot: false);
    $pins['busy']->level = false;

    expect(fn () => $panel->boot())->toThrow(JD79661Exception::class, 'BUSY stayed low for 15 ms')
        ->and($panel->hasBooted())->toBeFalse();
});

it('describes its bytes as packed two-bit palette codes', function (): void {
    [$panel] = quad(boot: false);
    $spec = $panel->formatSpec();

    expect($spec->pixel_format)->toBe(PixelFormat::ROW_MAJOR)
        ->and($spec->bit_depth)->toBe(BitDepth::B2)
        ->and($spec->palette->colors())->toBe([EInkColor::BLACK->value, EInkColor::WHITE->value, EInkColor::YELLOW->value, EInkColor::RED->value])
        ->and($spec->palette->codes())->toBe([0b00, 0b01, 0b10, 0b11]);
});

it('streams a whole frame, each row padded with white to the controller\'s 128-pixel rows', function (): void {
    [$panel, $spi] = quad();
    $before = count($spi->writes);
    $frame = quadFrame(JD79661Ink::RED);

    $panel->transmit(0, 0, $frame);
    $commands = $spi->commands($before);

    expect($commands)->toHaveCount(1)
        ->and($commands[0][0])->toBe(0x10)
        ->and(count($commands[0][1]))->toBe(32 * 250)
        ->and(array_slice($commands[0][1], 0, 32))->toBe([...array_fill(0, 31, 0xFF), 0x55])
        ->and(array_slice($commands[0][1], 32, 32))->toBe([...array_fill(0, 31, 0xFF), 0x55]);
});

it('streams a frame unpadded when the width fills its RAM rows', function (): void {
    [$panel, $spi] = quad(new JD79661Configuration(width: 128, height: 2));
    $before = count($spi->writes);

    $panel->transmit(0, 0, array_fill(0, 64, 0x39));

    expect($spi->commands($before))->toBe([[0x10, array_fill(0, 64, 0x39)]]);
});

it('takes whole frames only, with the right byte count', function (array $args, string $message): void {
    [$panel, $spi] = quad();
    $before = count($spi->writes);

    expect(fn () => $panel->transmit(...$args))->toThrow(JD79661Exception::class, $message)
        ->and(count($spi->writes))->toBe($before);
})->with([
    'window' => [[8, 0, array_fill(0, 10, 0x55), 8, 5], 'takes whole frames'],
    'size' => [[0, 0, array_fill(0, 31 * 10, 0x55), 122, 10], 'takes whole frames'],
    'byte count' => [[0, 0, array_fill(0, 100, 0x55)], 'needs 7750 bytes; got 100'],
]);

it('refreshes fully and waits for BUSY to rise; there is no partial refresh', function (): void {
    [$panel, $spi, $pins] = quad();
    $before = count($spi->writes);
    $pins['busy']->script = [false, false, true];
    $reads = $pins['busy']->reads;

    $panel->refresh();

    expect($spi->commands($before))->toBe([[0x12, [0x00]]])
        ->and($pins['busy']->reads - $reads)->toBe(3)
        ->and(fn () => $panel->refresh(RefreshMode::PARTIAL))->toThrow(JD79661Exception::class, 'has no partial refresh');
});

it('powers off and sleeps, and boots again from a hardware reset', function (): void {
    [$panel, $spi, $pins] = quad();
    $before = count($spi->writes);

    $panel->sleep();

    expect($spi->commands($before))->toBe([[0x02, [0x00]], [0x07, [0xA5]]])
        ->and($panel->hasBooted())->toBeFalse();

    $panel->boot();

    expect($pins['rst']->levels)->toBe([true, false, true, true, false, true]);
});

it('reads any configuration key and writes the register settings', function (): void {
    [$panel, $spi] = quad();
    $before = count($spi->writes);

    $panel->pll_control = new JD79661PLLControl(0x0A);

    expect($spi->commands($before))->toBe([[0x30, [0x0A]]])
        ->and($panel->pll_control->frame_rate)->toBe(0x0A)
        ->and($panel->busy_timeout_ms)->toBe(60_000)
        ->and(fn () => $panel->width = 10)->toThrow(JD79661Exception::class, "Invalid property 'width'");
});

it('throws when the bus refuses a write', function (): void {
    [$panel, $spi] = quad();
    $spi->answer = -1;

    expect(fn () => $panel->refresh())->toThrow(JD79661Exception::class, 'JD79661 SPI command 0x12 write failed: -1 of 1 bytes');
});

it('refuses a size the resolution register cannot hold and a BUSY timeout under 1 ms', function (): void {
    expect(fn () => new JD79661Configuration(width: 0))->toThrow(JD79661Exception::class, 'does not fit the resolution register')
        ->and(fn () => new JD79661Configuration(height: 65536))->toThrow(JD79661Exception::class, 'does not fit the resolution register')
        ->and(fn () => new JD79661Configuration(busy_timeout_ms: 0))->toThrow(JD79661Exception::class, 'busy_timeout_ms takes 1');
});

it('releases DC, RST and BUSY on close, leaving the bus to its driver', function (): void {
    [$panel, $spi, $pins] = quad();

    $panel->close();

    expect($pins['dc']->closed())->toBeTrue()
        ->and($pins['rst']->closed())->toBeTrue()
        ->and($pins['busy']->closed())->toBeTrue()
        ->and($spi->closed())->toBeFalse();
});

it('roots its exception at the framework circuit exception', function (): void {
    expect(JD79661Exception::busyTimeout(1))->toBeInstanceOf(CircuitException::class);
});
