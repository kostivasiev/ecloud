<?php

namespace App\Services\V2;

use GuzzleHttp\Client;

final class KingpinService
{
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
}
