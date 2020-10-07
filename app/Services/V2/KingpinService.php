<?php

namespace App\Services\V2;

use GuzzleHttp\Client;

final class KingpinService
{
    const INSTANCE_POWERSTATE_POWEREDON = 'poweredOn';
    const INSTANCE_POWERSTATE_POWEREDOFF = 'poweredOff';
    const INSTANCE_TOOLSRUNNINGSTATUS_RUNNING = 'guestToolsRunning';

    /**
     * @var Client
     */
    private $client;

    public function __construct($client)
    {
        $this->client = $client;
    }

    public function __call($name, $arguments)
    {
        return call_user_func_array([$this->client, $name], $arguments);
    }

    public function getInstance($instance)
    {
        $response = $this->get('/api/v2/vpc/' . $instance->vpc_id . '/instance/' . $instance->getKey());

        return json_decode($response->getBody()->getContents());
    }
}
