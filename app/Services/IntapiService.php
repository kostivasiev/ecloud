<?php

namespace App\Services;

use GuzzleHttp\Exception\RequestException;
use App\Exceptions\V1\IntapiServiceException;

class IntapiService
{
    public $response;
    public $responseData;

    protected $client;
    protected $headers;


    public function __construct($httpClient)
    {
        $this->client = $httpClient;
    }


    /**
     * Load Firewall Config
     *
     * @param $firewallId
     * @return mixed
     * @throws IntapiServiceException
     */
    public function getFirewallConfig($firewallId)
    {
        try {
            $response = $this->request('POST', '/firewall/get-config', [
                'form_params' => [
                    'server_id' => $firewallId
                ]
            ]);
        } catch (RequestException $exception) {
            throw new IntapiServiceException('Failed to load config for firewall #'.$firewallId.'', null, 502);
        }

        $data = $this->parseResponseData($response->getBody()->getContents());

        if (!$data->result) {
            throw new IntapiServiceException(end($data->errorset));
        }

        return $data->config;
    }

    /**
     * Makes a request to the UKFast Api.
     *
     * @param $method
     * @param $endpoint
     * @param array $options
     *
     * @return Response
     */
    public function request($method, $endpoint, $options = [])
    {
        return $this->response = $this->client->request($method, $endpoint, array_merge_recursive([
//            'debug' => true,
            'headers' => [
                'User-Agent' => 'service-'.env('APP_NAME').'/1.0',
                'Accept'     => 'application/json',
                'Content-Type' => 'application/x-www-form-urlencoded',
            ]
        ], $options));
    }

    /**
     * @param $json
     * @return mixed
     * @throws IntapiServiceException
     */
    protected function parseResponseData($json)
    {
        $data = json_decode($json);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new IntapiServiceException('failed to parse response data');
        }

        return $data;
    }
}
