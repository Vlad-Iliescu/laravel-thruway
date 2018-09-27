<?php

namespace LaravelThruway;


use GuzzleHttp\Psr7 as PSR;
use Psr\Http\Message\RequestInterface;
use Ratchet\RFC6455\Handshake\ClientNegotiator;
use React\EventLoop\LoopInterface;
use React\Promise\Deferred;
use React\Promise\RejectedPromise;
use React\Socket\ConnectionInterface;
use React\Socket\ConnectorInterface;
use WebSocket;

class Connector
{
    protected $_loop;
    protected $_connector;
    protected $_secureConnector;
    protected $_negotiator;

    public function __construct(LoopInterface $loop, ConnectorInterface $connector = null)
    {
        if (null === $connector) {
            $connector = new \React\Socket\Connector($loop, [
                'timeout' => 20
            ]);
        }
        $this->_loop = $loop;
        $this->_connector = $connector;
        $this->_negotiator = new ClientNegotiator();
    }

    /**
     * @param string $url
     * @param array $subProtocols
     * @param array $headers
     * @return \React\Promise\PromiseInterface
     */
    public function __invoke($url, array $subProtocols = [], array $headers = [])
    {
        try {
            $request = $this->generateRequest($url, $subProtocols, $headers);
            $uri = $request->getUri();
        } catch (\Exception $e) {
            return new RejectedPromise($e);
        }

        $secure = substr($url, 0, 3) === 'wss';
        $connector = $this->_connector;
        $port = $uri->getPort() ?: ($secure ? 443 : 80);
        $scheme = $secure ? 'tls' : 'tcp';
        $uriString = $scheme . '://' . $uri->getHost() . ':' . $port;

        return $connector
            ->connect($uriString)
            ->then(function (ConnectionInterface $conn) use ($request, $subProtocols) {
                $futureWsConn = new Deferred();

                $earlyClose = function () use ($futureWsConn) {
                    $futureWsConn->reject(new \RuntimeException('Connection closed before handshake'));
                };

                $stream = $conn;
                $stream->on('close', $earlyClose);
                $futureWsConn->promise()->then(function () use ($stream, $earlyClose) {
                    $stream->removeListener('close', $earlyClose);
                });

                $buffer = '';
                $headerParser =
                    function ($data) use ($stream, &$headerParser, &$buffer, $futureWsConn, $request, $subProtocols) {
                        $buffer .= $data;
                        if (strpos($buffer, "\r\n\r\n") == false) {
                            return;
                        }

                        $stream->removeListener('data', $headerParser);
                        $response = PSR\parse_response($buffer);
                        if (!$this->_negotiator->validateResponse($request, $response)) {
                            $futureWsConn->reject(new \DomainException(PSR\str($response)));
                            $stream->close();
                            return;
                        }

                        $acceptedProtocol = $response->getHeader('Sec-WebSocket-Protocol');
                        if (
                            (count($subProtocols) > 0) &&
                            count(array_intersect($subProtocols, $acceptedProtocol)) !== 1
                        ) {
                            $futureWsConn->reject(
                                new \DomainException(
                                    'Server did not respond with an expected Sec-WebSocket-Protocol'
                                ));
                            $stream->close();
                            return;
                        }
                        $futureWsConn->resolve(new WebSocket($stream, $response, $request));

                        $futureWsConn->promise()->then(function (WebSocket $conn) use ($stream) {
                            $stream->emit('data', [$conn->response->getBody(), $stream]);
                        });
                    };

                $stream->on('data', $headerParser);
                $stream->write(PSR\str($request));
                return $futureWsConn->promise();
            });
    }

    /**
     * @param string $url
     * @param array $subProtocols
     * @param array $headers
     * @throws \InvalidArgumentException
     * @return \Psr\Http\Message\RequestInterface
     */
    protected function generateRequest($url, array $subProtocols, array $headers)
    {
        $uri = PSR\uri_for($url);
        $scheme = $uri->getScheme();

        if (!in_array($scheme, ['ws', 'wss'])) {
            throw new \InvalidArgumentException(sprintf('Cannot connect to invalid URL (%s)', $url));
        }

        $uri = $uri->withScheme('HTTP');
        if (!$uri->getPort()) {
            $uri = $uri->withPort('wss' === $scheme ? 443 : 80);
        }

        $headers += ['User-Agent' => 'Ratchet/0.4'];
        $request = array_reduce(array_keys($headers), function (RequestInterface $request, $header) use ($headers) {
            return $request->withHeader($header, $headers[$header]);
        }, $this->_negotiator->generateRequest($uri));

        if (!$request->getHeader('Origin')) {
            $request = $request->withHeader(
                'Origin',
                str_replace('ws', 'http', $scheme) . '://' . $uri->getHost()
            );
        }

        if (count($subProtocols) > 0) {
            $protocols = implode(',', $subProtocols);
            if ($protocols != "") {
                $request = $request->withHeader('Sec-WebSocket-Protocol', $protocols);
            }
        }

        return $request;
    }

}