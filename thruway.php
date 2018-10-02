<?php

use React\EventLoop\Factory;
use React\ZMQ\Context;
use Thruway\ClientSession;
use Thruway\Peer\Client;
use Thruway\Peer\Router;
use Thruway\Transport\RatchetTransportProvider;

require 'vendor/autoload.php';

$pusher = new Client("realm1");

$loop = Factory::create();

$pusher->on('open', function (ClientSession $session) use ($loop) {
    $context = new Context($loop);
    $pull = $context->getSocket(ZMQ::SOCKET_PULL);
    $pull->bind('tcp://127.0.0.1:5555');

    $pull->on('message', function ($entry) use ($session) {
        $entryData = json_decode($entry, true);
        if (isset($entryData['ws_channel'])) {
            $session->publish($entryData['ws_channel'], [$entryData]);
        }
    });
});

$router = new Router($loop);
$router->addInternalClient($pusher);
$router->addTransportProvider(new RatchetTransportProvider("0.0.0.0", 7474));
try {
    $router->start();
} catch (Exception $e) {
    echo $e->getMessage();
}
