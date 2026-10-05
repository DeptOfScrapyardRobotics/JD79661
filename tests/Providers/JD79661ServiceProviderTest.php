<?php

use DeptOfScrapyardRobotics\Displays\JD79661\JD79661;
use DeptOfScrapyardRobotics\Displays\JD79661\Providers\JD79661ServiceProvider;
use DeptOfScrapyardRobotics\Displays\JD79661\Tests\Support\ConfigPathVessel;
use GeneralPurposeIO\IntegratedCircuits\CircuitRegistry;
use Voyager\Config\Repository;
use Voyager\NutsAndBolts\ServiceProvider;

it('registers the wiring config under circuits.jd79661, keeping anything the app already set', function (): void {
    $app = new ConfigPathVessel;
    $app->registerInstance('config', new Repository(['circuits' => ['jd79661' => ['default_config' => 'bench'], 'adxl345' => ['default_config' => 'i2c']]]));

    (new JD79661ServiceProvider($app))->register();
    $config = $app->make('config');

    expect($config->get('circuits.jd79661.default_config'))->toBe('bench')
        ->and($config->get('circuits.jd79661.configs.spi.busy.pin'))->toBe(3)
        ->and($config->get('circuits.adxl345'))->toBe(['default_config' => 'i2c']);
});

it('publishes the config into config/circuits under the jd79661-config tag', function (): void {
    $app = new ConfigPathVessel('/app/config');
    $app->registerInstance('config', new Repository);

    $provider = new JD79661ServiceProvider($app);
    $provider->register();
    $provider->boot();

    expect(ServiceProvider::pathsToPublish(JD79661ServiceProvider::class, 'jd79661-config'))->toBe([
        dirname(__DIR__, 2).'/config/jd79661.php' => '/app/config/circuits/jd79661.php',
    ]);
});

it('adds the panel to the circuit catalog when one is bound', function (): void {
    $app = new ConfigPathVessel;
    $app->registerInstance('config', new Repository);
    $app->registerInstance('circuit', $catalog = new CircuitRegistry);

    $provider = new JD79661ServiceProvider($app);
    $provider->register();
    $provider->boot();

    expect($catalog->listCircuits())->toBe(['jd79661' => JD79661::class]);
});
