<?php

namespace App\Services;

use GuzzleHttp\Client;

abstract class AbstractApioService
{
    /**
     * Default API version
     * @var string
     */
    protected $api_version = "v1";

    /**
     * @var Client
     */
    protected $client;

    /**
     * Headers to be sent
     * @var array
     */
    protected $headers = [];

    /**
     * An object returned by the other APIo service
     * @var \stdClass
     */
    protected $response;

    /**
     * AbstractApioService constructor.
     * @param $client
     * @param $host
     */
    public function __construct(Client $client)
    {
        $this->client = $client;

        $this->headers = [
            'User-Agent'           => 'service-' . env('APP_NAME') . '/1.0',
            'Accept'               => 'application/json',
            'X-consumer-custom-id' => '0-0'
        ];
    }

    /**
     * Scope for a particular reseller
     * @param $resellerID
     * @return $this
     */
    public function scopeResellerId($resellerID)
    {
        $this->headers['X-Reseller-Id'] = $resellerID;

        return $this;
    }

    /**
     * Send the HTTP request
     * @param       $method
     * @param $endpoint
     * @param array $options
     * @return bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function makeRequest($method, $endpoint, $options = [])
    {
        return $this->response = $this->client->request(
            $method,
            '/' . $this->api_version . '/' . $endpoint,
            array_merge_recursive([
                'debug'   => false,
                'headers' => $this->headers
            ], $options)
        );
    }

    /**
     * @param $json
     * @return mixed
     * @throws \Exception
     */
    protected function parseResponseData($json)
    {
        $data = json_decode($json);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('failed to parse response data');
        }

        return $data;
    }

    /**
     * Sets the services' client, used when rebinding the service for testing
     * @param Client $client
     */
    public function setClient(Client $client)
    {
        $this->client = $client;
    }
}
