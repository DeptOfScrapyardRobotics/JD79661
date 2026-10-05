<?php

namespace DeptOfScrapyardRobotics\Displays\JD79661\Providers;

use DeptOfScrapyardRobotics\Displays\JD79661\JD79661;
use Voyager\NutsAndBolts\ServiceProvider;

/**
 * The panel's wiring config lives under the circuits tree: config('circuits.jd79661'), published to
 * config/circuits/jd79661.php, which the config loader keys the same way.
 */
class JD79661ServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(dirname(__DIR__, 2).'/config/jd79661.php', 'circuits.jd79661');
    }

    public function boot(): void
    {
        $this->publishes([
            dirname(__DIR__, 2).'/config/jd79661.php' => $this->app->configPath('circuits/jd79661.php'),
        ], 'jd79661-config');

        // With the GPIO catalog installed, the panel is conjurable by slug: app('circuit')->conjure('jd79661').
        if ($this->app->isBound('circuit')) {
            $this->app->make('circuit')->addCircuit('jd79661', JD79661::class);
        }
    }
}
