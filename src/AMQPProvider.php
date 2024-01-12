<?php

namespace Hadenting\LaravelAmqp;

use Illuminate\Support\ServiceProvider;

class AMQPProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        // 发布配置文件
        $this->publishes([
            __DIR__ . '/../config/amqp.php' => config_path('amqp.php'),
        ]);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('amqp', function ($app) {
            return new AMQPManager();
        });
    }
}
