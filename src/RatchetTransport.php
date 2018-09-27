<?php

namespace LaravelThruway;

use React\EventLoop\LoopInterface;
use Thruway\Message\Message;
use Thruway\Transport\AbstractTransport;

class RatchetTransport extends AbstractTransport
{
    private $conn;

    /**
     * RatchetTransportProvider constructor.
     * @param $conn
     * @param LoopInterface $loop
     */
    public function __construct($conn, LoopInterface $loop)
    {
        $this->conn = $conn;
        $this->loop = $loop;
    }

    /**
     * @return mixed
     */
    public function getTransportDetails()
    {
        return [
            "type" => "ratchet"
        ];
    }

    /**
     * @param \Thruway\Message\Message $msg
     */
    public function sendMessage(Message $msg)
    {
        $this->conn->send($this->getSerializer()->serialize($msg));
    }
}