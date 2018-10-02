<?php
return [
    /*
    |--------------------------------------------------------------------------
    | Laravel Thruway Configuration
    |--------------------------------------------------------------------------
    */

    'class' => \LaravelThruway\Server::class,
    'realm' => 'realm',
    'host' => '0.0.0.0',
    'port' => '8080',
    'blackList' => [],
    'zmq' => [
        'host' => '127.0.0.1',
        'port' => 5555,
    ],
];
