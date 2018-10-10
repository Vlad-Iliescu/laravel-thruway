<?php

namespace LaravelThruway\Providers;

use Illuminate\Contracts\Broadcasting\Factory;
use Illuminate\Support\ServiceProvider;
use LaravelThruway\Broadcasting\Broadcaster\ZmqBroadcaster;
use LaravelThruway\Console\Commands\ThruwayServerCommand;
use LaravelThruway\Pusher;

class LaravelThruwayServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(Pusher::class, function ($app) {
            return new Pusher(config('thruway.zmq.host'), config('thruway.zmq.port'));
        });

        $this->app->singleton('command.thruway.serve', function () {
            return new ThruwayServerCommand();
        });
        $this->commands('command.thruway.serve');

        $this->mergeConfigFrom(__DIR__ . '/../config/thruway.php', 'thruway');
    }

    /**
     * Register routes, translations, views and publishers.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/ratchet.php' => config_path('thruway.php'),
        ]);

        $this->app->make(Factory::class)
            ->extend('zmq', function ($app) {
                return new ZmqBroadcaster($this->app[Pusher::class]);
            });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            'command.thruway.serve',
            Pusher::class
        ];
    }
}
