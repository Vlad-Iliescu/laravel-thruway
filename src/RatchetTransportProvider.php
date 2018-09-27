<?php

namespace LaravelThruway;

use React\EventLoop\LoopInterface;
use React\EventLoop\Timer\Timer;
use Thruway\Logging\Logger;
use Thruway\Peer\ClientInterface;
use Thruway\Serializer\DeserializationException;
use Thruway\Serializer\JsonSerializer;
use Thruway\Transport\AbstractClientTransportProvider;
use WebSocket;


class RatchetTransportProvider extends AbstractClientTransportProvider
{
    /**
     * @var string
     */
    private $URL;

    /**
     * Constructor
     *
     * @param string $URL
     */
    public function __construct($URL = "ws://127.0.0.1:8080/")
    {
        $this->URL = $URL;
    }

    /**
     * Start transport provider
     *
     * @param ClientInterface $client
     * @param \React\EventLoop\LoopInterface $loop
     */
    public function startTransportProvider(ClientInterface $client, LoopInterface $loop)
    {
        $this->client = $client;
        $this->loop = $loop;

        $connector = new Connector($loop);
        $connection = $connector($this->URL, ['wamp.2.json'], []);

        $runHasBeenCalled = false;

        $loop->addTimer(Timer::MIN_INTERVAL, function () use (&$runHasBeenCalled) {
            $runHasBeenCalled = true;
        });

        register_shutdown_function(function () use ($loop, &$runHasBeenCalled) {
            if (!$runHasBeenCalled) {
                $loop->run();
            }
        });

        $connection->then(
            function (WebSocket $conn) {
                Logger::info($this, "Pawl has connected");
                $transport = new RatchetTransport($conn, $this->loop);
                $transport->setSerializer(new JsonSerializer());

                $this->client->onOpen($transport);

                $conn->on(
                    'message',
                    function ($msg) use ($transport) {
                        Logger::debug($this, "Received: {$msg}");
                        try {
                            $this->client->onMessage($transport, $transport->getSerializer()->deserialize($msg));
                        } catch (DeserializationException $e) {
                            Logger::warning($this, "Deserialization exception occurred.");
                        } catch (\Exception $e) {
                            Logger::warning($this, "Exception occurred during onMessage: " . $e->getMessage());
                        }
                    }
                );

                $conn->on(
                    'close',
                    function ($conn) {
                        Logger::info($this, "Pawl has closed");
                        $this->client->onClose('close');
                    }
                );

            },
            function ($e) {
                $this->client->onClose('unreachable');
                Logger::info($this, "Could not connect: {$e->getMessage()}");
            });
    }
}