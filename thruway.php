<?php

use LaravelThruway\RatchetTransportProvider;
use Thruway\Peer\Client;

require 'vendor/autoload.php';

$pusher = new Client("realm1");

$loop = React\EventLoop\Factory::create();

$pusher->on('open', function ($session) use ($loop) {
    $context = new React\ZMQ\Context($loop);
    $pull = $context->getSocket(ZMQ::SOCKET_PULL);
    $pull->bind('tcp://127.0.0.1:5555');

    $pull->on('message', function ($entry) use ($session) {
        $entryData = json_decode($entry, true);
        if (isset($entryData['category'])) {
            $session->publish($entryData['category'], [$entryData]);
        }
    });
});

$router = new Thruway\Peer\Router($loop);
$router->addInternalClient($pusher);
$router->addTransportProvider(new Thruway\Transport\RatchetTransportProvider("0.0.0.0", 7474));
$router->start();