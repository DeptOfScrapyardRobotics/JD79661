<?php

/*
| Proven against recording fakes: every command and data byte the panel would
| see, tagged by the DC level it went out under, every RST level, and a
| scripted BUSY line (low while busy). Nothing here touches a bus. The live
| check is a 122 x 250 four-colour JD79661 panel on an FT232H.
*/

use DeptOfScrapyardRobotics\Displays\JD79661\Tests\Support\ConfigPathVessel;
use DeptOfScrapyardRobotics\Displays\JD79661\Tests\Support\FakeDigitalIOConnectionDriver;
use DeptOfScrapyardRobotics\Displays\JD79661\Tests\Support\FakeSPIConnectionDriver;
use GeneralPurposeIO\Digital\DigitalOConnectionManager;
use GeneralPurposeIO\IntegratedCircuits\CircuitRegistry;
use GeneralPurposeIO\SPI\SPIConnectionManager;
use Voyager\Config\Repository;
use Voyager\Vessel\ControlPanel;

/*
| A Venusian app's core defines config() over the container's config repository;
| CircuitRegistry::conjure() calls it. A package suite has no core, so this
| stands in for it the same way.
*/
if (! function_exists('config')) {
    function config(array|string|null $key = null, mixed $default = null): mixed
    {
        $config = ControlPanel::getInstance()->make('config');

        return match (true) {
            is_null($key) => $config,
            is_array($key) => $config->set($key),
            default => $config->get($key, $default),
        };
    }
}

/**
 * The shared container as an app sets it up: config, the circuit catalog, and the two protocol managers, each
 * with a 'fake' driver.
 *
 * @return array{spi: FakeSPIConnectionDriver, digital: FakeDigitalIOConnectionDriver, app: ConfigPathVessel}
 */
function fakeBench(array $config = []): array
{
    $app = new ConfigPathVessel;
    $app->registerInstance('config', new Repository($config));
    $app->registerInstance('circuit', new CircuitRegistry);
    ControlPanel::setInstance($app);

    $bench = [
        'spi' => new FakeSPIConnectionDriver,
        'digital' => new FakeDigitalIOConnectionDriver,
        'app' => $app,
    ];

    $app->registerInstance('gpio.spi', (new SPIConnectionManager($app))->extend('fake', fn () => $bench['spi']));
    $app->registerInstance('gpio.digital', (new DigitalOConnectionManager($app))->extend('fake', fn () => $bench['digital']));

    return $bench;
}

pest()->afterEach(function (): void {
    ControlPanel::setInstance(null);
})->in(__DIR__);

/**
 * @return array{0: \DeptOfScrapyardRobotics\Displays\JD79661\JD79661, 1: \DeptOfScrapyardRobotics\Displays\JD79661\Tests\Support\FakeSPITransport, 2: array{dc: \DeptOfScrapyardRobotics\Displays\JD79661\Tests\Support\FakeOutputPin, rst: \DeptOfScrapyardRobotics\Displays\JD79661\Tests\Support\FakeOutputPin, busy: \DeptOfScrapyardRobotics\Displays\JD79661\Tests\Support\FakeBusyPin}}
 */
function quad(?\DeptOfScrapyardRobotics\Displays\JD79661\JD79661Configuration $config = null, bool $boot = true): array
{
    $pins = [
        'dc' => new \DeptOfScrapyardRobotics\Displays\JD79661\Tests\Support\FakeOutputPin(1),
        'rst' => new \DeptOfScrapyardRobotics\Displays\JD79661\Tests\Support\FakeOutputPin(2),
        'busy' => new \DeptOfScrapyardRobotics\Displays\JD79661\Tests\Support\FakeBusyPin(3),
    ];
    $spi = new \DeptOfScrapyardRobotics\Displays\JD79661\Tests\Support\FakeSPITransport($pins['dc']);
    $transport = new \DeptOfScrapyardRobotics\Displays\JD79661\Transports\JD79661SPITransport($spi, $pins['dc'], $pins['rst'], $pins['busy']);

    return [new \DeptOfScrapyardRobotics\Displays\JD79661\JD79661($transport, $config ?? new \DeptOfScrapyardRobotics\Displays\JD79661\JD79661Configuration, boot_now: $boot), $spi, $pins];
}

/** The reference bring-up for a 122 x 250 panel, one entry per command with its parameters. */
function quadBootCommands(): array
{
    return [
        [0x4D, [0x78]],
        [0x00, [0x8F, 0x29]],
        [0x01, [0x07, 0x00]],
        [0x03, [0x10, 0x54, 0x44]],
        [0x06, [0x05, 0x00, 0x3F, 0x0A, 0x25, 0x12, 0x1A]],
        [0x50, [0x37]],
        [0x60, [0x02, 0x02]],
        [0x61, [0x00, 0x80, 0x00, 0xFA]],
        [0xE7, [0x1C]],
        [0xE3, [0x22]],
        [0xB4, [0xD0]],
        [0xB5, [0x03]],
        [0xE9, [0x01]],
        [0x30, [0x08]],
        [0x04, []],
    ];
}
