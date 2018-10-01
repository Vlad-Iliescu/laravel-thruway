<?php

namespace LaravelThruway;


interface PusherInterface
{
    /**
     * This is meant to be overridden so that the client can do its
     * thing
     * @param string $msg
     */
    public function onEntry($msg);
}