<?php

namespace Butler\Guru;

use Butler\Guru\Commands\ListenForEvents;
use Butler\Guru\Commands\PublishEvent;
use Butler\Guru\Drivers\Amqp as AmqpDriver;
use Butler\Guru\Drivers\File as FileDriver;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $driver = config('butler.guru.driver', 'file');
        if ($driver === 'file') {
            $this->app->bind('Butler\Guru\Drivers\Driver', FileDriver::class);
            if ($this->app->runningInConsole()) {
                $this->commands([
                    PublishEvent::class,
                ]);
            }
        } elseif ($driver === 'rabbitmq') {
            $this->app->bind('Butler\Guru\Drivers\Driver', AmqpDriver::class);
            if ($this->app->runningInConsole()) {
                $this->commands([
                    ListenForEvents::class,
                    PublishEvent::class,
                ]);
            }
        } else {
            throw new \InvalidArgumentException("Invalid guru driver: {$driver}");
        }

        $this->app->bind(EventRouter::class, function () {
            return new EventRouter(config('butler.guru.events'));
        });
    }

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        $this->setupConfig($this->app);
        app('events')->listen(GuruEvent::class, GuruDispatcher::class);
    }

    private function setupConfig($app)
    {
        if ($app->runningInConsole()) {
            $this->publishes([realpath(__DIR__ . '/../config/butler.php') => config_path('butler.php')]);
            $this->publishes([realpath(__DIR__ . '/../config/amqp.php') => config_path('amqp.php')]);
        }
    }
}
