<?php

namespace LaravelThruway;

use LaravelThruway\Exceptions\ThruwayServerException;
use React\ZMQ\Context;
use Thruway\Logging\Logger;
use Thruway\Peer\Client;
use ZMQ;

class Server extends Client implements ServerInterface
{
    /**
     * Host for ZMQ binding
     * @var string
     */
    protected $zmqHost = '127.0.0.1';

    /**
     * Port for ZMQ binding
     * @var int
     */
    protected $zmqPort = 5555;

    /**
     * ZMQ socket type
     * @var int
     */
    protected $zmqSocketType = ZMQ::SOCKET_PULL;

    /**
     * @param string $zmqHost
     * @return Server
     */
    public function setZmqHost($zmqHost)
    {
        $this->zmqHost = $zmqHost;
        return $this;
    }

    /**
     * @param int $zmqPort
     * @return Server
     */
    public function setZmqPort($zmqPort)
    {
        $this->zmqPort = $zmqPort;
        return $this;
    }

    /**
     * @param int $zmqSocketType
     * @return Server
     */
    public function setZmqSocketType($zmqSocketType)
    {
        $this->zmqSocketType = $zmqSocketType;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function onSessionStart($session, $transport)
    {
        Logger::info($this, "Client onSessionStart");

        $this->createSubscriptions($session, $transport);

        $context = new Context($this->getLoop());
        $pull = $context->getSocket($this->zmqSocketType);
        try {
            $pull->bind("tcp://{$this->zmqHost}:{$this->zmqPort}");
        } catch (\ZMQSocketException $e) {
            Logger::error($this, "Cannot create ZMQ context: {$e->getMessage()}");
            throw new ThruwayServerException($e->getMessage(), $e->getCode(), $e);
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

        if (!isset($entryData['ws_channel'])) {
            return;
        }
        $channel = $entryData['ws_channel'];
        unset($entryData['ws_channel']);

        $this->getSession()->publish($channel, [$entryData]);
    }

    /**
     * @inheritdoc
     */
    public function createSubscriptions($session, $transport) { }
}
