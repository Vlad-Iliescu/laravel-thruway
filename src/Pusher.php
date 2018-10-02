<?php

namespace LaravelThruway;


use LaravelThruway\Exceptions\PusherException;

class Pusher
{
    /**
     * @var \ZMQContext
     */
    protected $context;

    /**
     * @var \ZMQSocket
     */
    protected $socket;

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
    protected $zmqSocketType = \ZMQ::SOCKET_PUSH;

    /**
     * Pusher constructor.
     * @param string $host
     * @param int $port
     */
    public function __construct($host = '127.0.0.1', $port = 5555)
    {
        $this->zmqHost = $host;
        $this->zmqPort = $port;
        $this->context = new \ZMQContext();
        try {
            $this->socket = $this->context->getSocket($this->zmqSocketType);
            $this->socket->connect("tcp://{$this->zmqHost}:{$this->zmqPort}");
        } catch (\ZMQSocketException $e) {
            throw new PusherException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @param string $channel
     * @param array|mixed $message
     */
    public function push($channel, $message)
    {
        if (is_array($message)) {
            $message['ws_channel'] = $channel;
        } elseif (is_object($message)) {
            $message = json_decode(json_encode($message), true);
            $message['ws_channel'] = $channel;
        } else {
            $message = [
                'ws_channel' => $channel,
                $message
            ];
        }

        try {
            $this->socket->send(json_encode($message));
        } catch (\ZMQSocketException $e) {
            throw new PusherException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
