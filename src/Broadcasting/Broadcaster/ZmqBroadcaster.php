<?php

namespace LaravelThruway\Broadcasting\Broadcaster;


use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\Broadcasters\Broadcaster;
use Illuminate\Support\Str;
use LaravelThruway\Pusher;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class ZmqBroadcaster extends Broadcaster
{
    /**
     * @var Pusher $pusher
     */
    private $pusher;

    /**
     * ZmqBroadcaster constructor.
     * @param Pusher $pusher
     */
    public function __construct(Pusher $pusher)
    {
        $this->pusher = $pusher;
    }


    /**
     * Authenticate the incoming request for a given channel.
     *
     * @param  \Illuminate\Http\Request $request
     * @return mixed
     */
    public function auth($request)
    {
        if (
            Str::startsWith($request->channel_name, ['private-', 'presence-']) &&
            !$request->user()
        ) {
            throw new AccessDeniedHttpException;
        }
        $channelName = Str::startsWith($request->channel_name, 'private-')
            ? Str::replaceFirst('private-', '', $request->channel_name)
            : Str::replaceFirst('presence-', '', $request->channel_name);

        return parent::verifyUserCanAccessChannel(
            $request,
            $channelName
        );
    }

    /**
     * Return the valid authentication response.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  mixed $result
     * @return mixed
     */
    public function validAuthenticationResponse($request, $result)
    {
        if (is_bool($result)) {
            return json_encode($result);
        }

        return json_encode(['channel_data' => [
            'user_id' => $request->user()->getAuthIdentifier(),
            'user_info' => $result,
        ]]);
    }

    /**
     * Broadcast the given event.
     *
     * @param  Channel[] $channels
     * @param  string $event
     * @param  array $payload
     * @return void
     */
    public function broadcast(array $channels, $event, array $payload = [])
    {
        $payload['ws_event'] = $event;
        foreach ($channels as $channel) {
            $this->pusher->push($channel->name, $payload);
        }
    }
}
