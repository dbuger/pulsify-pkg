<?php
namespace Impulse\Pulsifier;

use Illuminate\Foundation\Application as LaravelApplication;
use Illuminate\Support\ServiceProvider;
use Impulse\Pulsifier\Console\PulsifyCommand;

class PulsifierServiceProvider extends ServiceProvider
{
    public function boot(){
        $this->publishes([
            __DIR__.'/config/pulsifier.php' => config_path('pulsifier.php'),
        ]);
    }

    public function register()
    {
        $this->app->singleton('command.pulsify', function () {
            return new PulsifyCommand;
        });

        $this->commands(['command.pulsify']);

        $this->mergeConfigFrom(
            __DIR__."/config/pulsifier.php",
            'pulsifier'
        );
    }

    public function provides()
    {
        return ['command.pulsify'];
    }
}