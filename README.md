# laravel-amqp

laravel 快速使用 amqp

## 使用说明

### 1. 安装

`composer require hadenting/laravel-amqp`

### 2. 发布

`php artisan vendor:publish --provider="Hadenting\Amqp\MongooerConrmqProvider"`

生成配置文件`amqp.php`，插件会默认使用 `connections` 数组中的 `default` 连接。


### 3. 生产和消费

使用sendJson快捷的发送json

```
AMQP::connection()->sendJson("exchange", "queue", "routingKey", '{"a":"1"}');
```

使用sendMessage发送

```
AMQP::connection()->sendMessage("exchange", "queue", "routingKey",
    (new AMQPMessage('{"a":"1"}', [
        'content_type' => 'application/json',
        'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT
    ])));
```

使用listener监听

```
AMQP::connection()->listener("exchange", "queue", "routingKey", function (AMQPMessage $message) {
    
});
```
