<?php

require 'vendor/autoload.php';

use LaravelThruway\Pusher;
use Thruway\Peer\Router;
use Thruway\Transport\RatchetTransportProvider;

$router = new Router();
$realm = "realm1";

$router->addInternalClient(new Pusher($realm, $router->getLoop()));
$router->addTransportProvider(new RatchetTransportProvider("0.0.0.0", 7474));
$router->start();

