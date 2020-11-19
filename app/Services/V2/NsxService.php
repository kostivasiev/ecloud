<?php

namespace App\Services\V2;

use GuzzleHttp\Client;

final class NsxService
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var string
     */
    private $edgeClusterId;

    public function __construct($client, $edgeClusterId)
    {
        $this->client = $client;
        $this->edgeClusterId = $edgeClusterId;
    }

    public function __call($name, $arguments)
    {
        return call_user_func_array([$this->client, $name], $arguments);
    }

    public function getEdgeClusterId()
    {
        return $this->edgeClusterId;
    }
}
