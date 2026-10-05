<?php

use DeptOfScrapyardRobotics\Displays\JD79661\JD79661;
use DeptOfScrapyardRobotics\Displays\JD79661\JD79661Exception;
use DeptOfScrapyardRobotics\Displays\JD79661\Providers\JD79661ServiceProvider;
use GeneralPurposeIO\Contracts\SPI\SPIMode;

/** The FT232H bench wiring, with overrides. */
function quadWiring(array $overrides = []): array
{
    return array_replace_recursive([
        'default_config' => 'spi',
        'configs' => ['spi' => [
            'driver' => 'fake',
            'device' => 'ft232h',
            'chip_select' => 0,
            'dc' => ['driver' => 'fake', 'device' => 'ft232h', 'pin' => 1],
            'rst' => ['driver' => 'fake', 'device' => 'ft232h', 'pin' => 2],
            'busy' => ['driver' => 'fake', 'device' => 'ft232h', 'pin' => 3],
        ]],
    ], $overrides);
}

function quadBench(array $wiring): array
{
    $bench = fakeBench(['circuits' => ['jd79661' => $wiring]]);
    $provider = new JD79661ServiceProvider($bench['app']);
    $provider->register();
    $provider->boot();

    return $bench;
}

it('conjures a booted panel: bus in mode 0 at 10 MHz, then DC, RST and BUSY on the same device', function (): void {
    $bench = quadBench(quadWiring());

    $panel = $bench['app']->make('circuit')->conjure('jd79661');

    expect($panel)->toBeInstanceOf(JD79661::class)
        ->and($panel->hasBooted())->toBeTrue()
        ->and($bench['spi']->settingsOf('ft232h')->mode)->toBe(SPIMode::MODE_0)
        ->and($bench['spi']->slaves['ft232h:0']->clock())->toBe(10_000_000)
        ->and($bench['digital']->opened)->toBe(['ft232h'])
        ->and($bench['digital']->outputs['ft232h:2']->levels)->toBe([true, false, true])
        ->and($bench['digital']->inputs)->toHaveKey('ft232h:3');
});

it('takes the panel size and clock from config', function (): void {
    $bench = quadBench(quadWiring(['configs' => ['spi' => ['width' => 128, 'height' => 296, 'speed' => 4_000_000]]]));

    $panel = $bench['app']->make('circuit')->conjure('jd79661');

    expect([$panel->width(), $panel->height()])->toBe([128, 296])
        ->and($bench['spi']->slaves['ft232h:0']->clock())->toBe(4_000_000);
});

it('refuses a clock below 1 Hz or a mode that does not exist before touching the bus', function (array $spi, string $message): void {
    $bench = quadBench(quadWiring(['configs' => ['spi' => $spi]]));

    expect(fn () => $bench['app']->make('circuit')->conjure('jd79661'))->toThrow(JD79661Exception::class, $message)
        ->and($bench['spi']->opened)->toBe([]);
})->with([
    'clock' => [['speed' => 0], 'SPI clock 0 Hz'],
    'mode' => [['mode' => 4], 'SPI mode 4 does not exist'],
]);

it('shares a bus in the configured mode and refuses one in another', function (int $bus_mode, bool $shared): void {
    $bench = quadBench(quadWiring());
    $bench['app']->make('gpio.spi')->driver('fake')->connectTo('ft232h')->mode($bus_mode)->register();

    $conjure = fn () => $bench['app']->make('circuit')->conjure('jd79661');

    $shared
        ? expect($conjure())->toBeInstanceOf(JD79661::class)
        : expect($conjure)->toThrow(JD79661Exception::class, 'runs in mode 3; this panel is configured for mode 0');
})->with([
    'mode 0' => [0, true],
    'mode 3' => [3, false],
]);

it('names a DC, RST or BUSY pin config missing its driver, device or pin', function (string $line): void {
    $wiring = quadWiring();
    unset($wiring['configs']['spi'][$line]['device']);
    $bench = quadBench($wiring);

    expect(fn () => $bench['app']->make('circuit')->conjure('jd79661'))->toThrow(JD79661Exception::class, "needs its {$line} pin");
})->with(['dc', 'rst', 'busy']);

it('builds without booting when boot_now is false', function (): void {
    $bench = quadBench(quadWiring(['configs' => ['spi' => ['boot_now' => false]]]));

    $panel = $bench['app']->make('circuit')->conjure('jd79661');

    expect($panel->hasBooted())->toBeFalse()
        ->and($bench['spi']->slaves['ft232h:0']->writes)->toBe([]);
});
