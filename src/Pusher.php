<?php

namespace LaravelThruway;

use React\ZMQ\Context;
use Thruway\ClientSession;
use Thruway\Logging\Logger;
use Thruway\Message\WelcomeMessage;
use Thruway\Peer\Client;
use ZMQ;

class Pusher extends Client implements PusherInterface
{
    /**
     * @inheritdoc
     */
    public function onSessionStart($session, $transport)
    {
        Logger::info($this, "Client onSessionStart");

        $context = new Context($this->getLoop());
        $pull = $context->getSocket(ZMQ::SOCKET_PULL);
        try {
            $pull->bind('tcp://127.0.0.1:5555');
        } catch (\ZMQSocketException $e) {
            Logger::error($this, "Cannot create ZMQ context: {$e->getMessage()}");
            throw new PusherException($e->getMessage(), $e->getCode(), $e);
        }
        $pull->on('message', [$this, 'onEntry']);
    }

    /**
     * @inheritdoc
     */
    public function onEntry($msg)
    {
        Logger::debug($this, "Client onEntry: {$msg}");

        $entryData = json_decode($msg, true);

        if (!isset($entryData['category'])) {
            return;
        }

        $this->getSession()->publish($entryData['category'], [$entryData]);
    }
}