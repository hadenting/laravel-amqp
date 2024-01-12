<?php

namespace Hadenting\LaravelAmqp;

use Hadenting\LaravelAmqp\Facades\AMQP;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exception\AMQPChannelClosedException;
use PhpAmqpLib\Exception\AMQPConnectionBlockedException;
use PhpAmqpLib\Exception\AMQPHeartbeatMissedException;
use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Message\AMQPMessage;

class AMQPConnection
{
    /**
     * @var AMQPStreamConnection
     */
    protected $connection;

    protected $maxReconnectTimes = 3;
    protected $reconnectTimes = 0;

    protected $consumerTag = 'router';

    public function __construct(array $config)
    {
        $this->connection = new AMQPStreamConnection(
            $config['host'],
            $config['port'],
            $config['user'],
            $config['password'],
            $config['vhost'] ?? '/',
            $config['insist'] ?? false,
            $config['login_method'] ?? 'AMQPLAIN',
            $config['login_response'] ?? null,
            $config['locale'] ?? 'en_US',
            $config['connection_timeout'] ?? 3.0,
            $config['read_write_timeout'] ?? 3.0,
            $config['context'] ?? null,
            $config['keepalive'] ?? false,
            $config['heartbeat'] ?? 0,
            $config['channel_rpc_timeout'] ?? 0.0,
            $config['ssl_protocol'] ?? null
        );
    }

    /**
     * @param string $exchange
     * @param string $queue
     * @param string $routingKey
     * @param string $jsonStr
     * @return void
     */
    public function sendJson(string $exchange, string $queue, string $routingKey, string $jsonStr)
    {
        $message = new AMQPMessage($jsonStr, array('content_type' => 'application/json', 'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT));
        $this->sendMessage($exchange, $queue, $routingKey, $message);
    }

    /**
     * @param string $exchange
     * @param string $queue
     * @param string $routingKey
     * @param AMQPMessage $AMQPMessage
     * @return void
     */
    public function sendMessage(string $exchange, string $queue, string $routingKey, AMQPMessage $AMQPMessage)
    {
        $this->handle($exchange, $queue, $routingKey, function (AMQPChannel $channel) use ($AMQPMessage, $exchange, $routingKey) {
            $channel->basic_publish($AMQPMessage, $exchange, $routingKey);
        });
    }

    /**
     * @param string $exchange
     * @param string $queue
     * @param string $routingKey
     * @param \Closure $callback
     * @return mixed
     */
    public function listener(string $exchange, string $queue, string $routingKey, \Closure $callback)
    {
        while (true) {
            $this->handle($exchange, $queue, $routingKey, function (AMQPChannel $channel) use ($callback, $queue) {
                $this->doSomeThing($channel, $queue, $callback);
            });
        }
    }

    /**
     * @param AMQPChannel $channel
     * @param string $queue
     * @param \Closure $callback
     * @return void
     * @throws \ErrorException
     */
    private function doSomeThing(AMQPChannel $channel, string $queue, \Closure $callback)
    {
        $channel->basic_qos(null, 10, null);
        //无论如何都会确认，所以异常需要捕获并且记录日志
        $processMessage = function (AMQPMessage $message) use ($callback) {
            try {
                call_user_func($callback, $message);
                $message->ack();
            } catch (\Throwable $e) {
                $message->ack();
            }
            // Send a message with the string "quit" to cancel the consumer.
            if ($message->body === 'quit') {
                $message->getChannel()->basic_cancel($message->getConsumerTag());
            }
        };
        $channel->basic_consume($queue, $this->consumerTag, false, false, false, false, $processMessage);
        while ($channel->is_consuming()) {
            $channel->wait();
        }
    }

    /**
     * @param string $exchange
     * @param string $queue
     * @param string $routingKey
     * @param \Closure $callback
     * @return void
     * @throws \Exception
     */
    private function handle(string $exchange, string $queue, string $routingKey, \Closure $callback)
    {
        try {
            if (!$this->connection->isConnected() || $this->connection->isBlocked()) {
                $this->connection->reconnect();
            }
            $channel = $this->connection->channel(1);
            $channel->exchange_declare($exchange, AMQPExchangeType::DIRECT, false, true, false);
            $channel->queue_declare($queue, false, true, false, false);
            $channel->queue_bind($queue, $exchange, $routingKey);
            call_user_func($callback, $channel);
            $this->reconnectTimes = 0;
        } catch (AMQPChannelClosedException|AMQPConnectionBlockedException|AMQPHeartbeatMissedException $exception) {
            $this->reconnectTimes += 1;
            if ($this->reconnectTimes <= $this->maxReconnectTimes) {
                $this->handle($exchange, $queue, $routingKey, $callback);
            } else {
                throw $exception;
            }
        }
    }

}
