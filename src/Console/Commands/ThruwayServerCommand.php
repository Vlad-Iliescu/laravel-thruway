<?php

namespace LaravelThruway\Console\Commands;

use Illuminate\Console\Command;
use LaravelThruway\Exceptions\ThruwayServerException;
use Symfony\Component\Console\Input\InputOption;
use Thruway\Logging\Logger;
use Thruway\Peer\Router;
use Thruway\Transport\RatchetTransportProvider;

class ThruwayServerCommand extends Command
{
    /**
     * The name of the console command.
     * @var string
     */
    protected $name = 'thruway:serve';

    /**
     * The console command description.
     * @var string
     */
    protected $description = 'Start Thruway Server';

    /**
     * Server host.
     * @var string
     */

    protected $host;

    /**
     * Server port.
     * @var int
     */
    protected $port;

    /**
     * Realm
     * @var string
     */
    protected $realm;

    /**
     * The class to use for the server.
     *
     * @var string
     */
    protected $class;

    /**
     * Server router
     * @var Router
     */
    protected $router;

    /**
     * @var \LaravelThruway\Server
     */
    protected $client;


    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['host', null, InputOption::VALUE_OPTIONAL, 'Ratchet server host', config('thruway.host', '0.0.0.0')],
            ['port', 'p', InputOption::VALUE_OPTIONAL, 'Ratchet server port', config('thruway.port', 8080)],
            ['class', null, InputOption::VALUE_OPTIONAL, 'Class that implements PusherInterface.', config('thruway.class')],
            ['realm', null, InputOption::VALUE_OPTIONAL, 'Realm.', config('thruway.realm')],
        ];
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->host = $this->option('host');
        $this->port = intval($this->option('port'));
        $this->class = $class = $this->option('class');
        $this->realm = $this->option('realm');

        $this->router = new Router();
        $this->client = new $class($this->realm, $this->router->getLoop());
        $this->client->setZmqHost(config('thruway.zmq.host', '0.0.0.0'))
            ->setZmqPort(config('thruway.zmq.port', 5555));

        $this->router->addInternalClient($this->client);
        $this->router->addTransportProvider(new RatchetTransportProvider($this->host, $this->port));

        try {
            $this->router->start();
        } catch (\Exception $e) {
            Logger::error($this, "Cannot start server: {$e->getMessage()}");
            throw new ThruwayServerException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
