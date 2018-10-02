<?php

use LaravelThruway\Pusher;

require 'vendor/autoload.php';

$entryData = array(
    'title' => "my_title",
    'article' => "my_article",
    'when' => time()
);


// This is our new stuff
$pusher = new Pusher();
$pusher->push('com.myapp.hello', $entryData);
