<?php

require 'vendor/autoload.php';

$entryData = array(
    'category' => "com.myapp.hello",
    'title' => "my_title",
    'article' => "my_article",
    'when' => time()
);


// This is our new stuff
$context = new ZMQContext();
$socket = $context->getSocket(ZMQ::SOCKET_PUSH, 'my pusher');
$socket->connect("tcp://localhost:5555");

$socket->send(json_encode($entryData));