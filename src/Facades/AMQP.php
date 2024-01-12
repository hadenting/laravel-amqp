<?php

namespace Hadenting\LaravelAmqp\Facades;

use Hadenting\LaravelAmqp\AMQPManager;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \Hadenting\LaravelAmqp\AMQPConnection connection(string $name = "default")
 * @see AMQPManager
 *
 */
class AMQP extends Facade
{

    protected static function getFacadeAccessor(): string
    {
        return 'amqp';
    }
}
