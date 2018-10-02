<?php

require 'vendor/autoload.php';

use LaravelThruway\Server;
use Thruway\Peer\Router;
use Thruway\Transport\RatchetTransportProvider;

$router = new Router();
$realm = "realm1";

$router->addInternalClient(new Server($realm, $router->getLoop()));
$router->addTransportProvider(new RatchetTransportProvider("0.0.0.0", 7474));
$router->start();

