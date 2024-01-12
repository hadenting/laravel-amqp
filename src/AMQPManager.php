<?php

namespace Hadenting\LaravelAmqp;

class AMQPManager
{
    protected $config;

    protected $connections = [];

    /**
     * 构造方法
     */
    public function __construct()
    {
        $this->config = config("amqp");
    }

    public function connection($name = "default")
    {
        if (!isset($this->connections[$name])) {
            if (!isset($this->config["connections"][$name])) {
                throw new \RuntimeException("amqp connection " . $name . " is not exists");
            }
            $config = $this->config["connections"][$name];
            $this->connections[$name] = new AMQPConnection($config);
        }
        return $this->connections[$name];
    }

    /**
     * 动态将方法传递给默认数据
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call(string $method, array $parameters)
    {
        return $this->$method(...$parameters);
    }

}
