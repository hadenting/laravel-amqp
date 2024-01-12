<?php
return [
    "connections" => [
        "default" => [
            "host" => env("AMQP_HOST"),
            "port" => env("AMQP_PORT"),
            "vhost" => env("AMQP_VHOST"),
            "user" => env("AMQP_USER"),
            "password" => env("AMQP_PASSWORD"),
            "insist" => false,
            "login_method" => "AMQPLAIN",
            "login_response" => null,
            "locale" => "en_US",
            "connection_timeout" => 60,
            "read_write_timeout" => 60,
            "context" => null,
            "keepalive" => true,
            "heartbeat" => 30 * 1,
            "channel_rpc_timeout" => 0.0,
            "ssl_protocol" => null,
        ],
    ],
];
