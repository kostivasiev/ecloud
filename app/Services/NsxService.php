<?php

namespace App\Services;

use GuzzleHttp\Client;

final class NsxService
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

    public function getEdgeClusterId()
    {
        return '8bc61267-583e-4988-b5d9-16b46f7fe900';
    }
}
