<?php

namespace LaravelThruway;


use Thruway\ClientSession;
use Thruway\Transport\TransportInterface;

interface ServerInterface
{
    /**
     * This is meant to be overridden so that the client can do its
     * thing
     * @param string $msg
     */
    public function onEntry($msg);

    /**
     * @param ClientSession $session
     * @param TransportInterface $transport
     */
    public function createSubscriptions($session, $transport);
}
