<?php

namespace App\Services;

use App\Exceptions\V1\NetworkingServiceException;
use GuzzleHttp\Exception\RequestException;

class NetworkingService
{
    public $response;
    public $responseData;

    protected $client;
    protected $headers;


    public function __construct($httpClient)
    {
        $this->client = $httpClient;

        $this->headers = [
            'User-Agent' => 'service-' . env('APP_NAME') . '/1.0',
            'Accept' => 'application/json',
            'X-consumer-custom-id' => '0-0',
        ];
    }

    public function scopeResellerID($resellerID)
    {
        $this->headers['X-Reseller-Id'] = $resellerID;
    }

    public function getFirewallConfig($firewallId)
    {
        try {
            $response = $this->request('GET', '/v1/firewalls/' . $firewallId . '/config');
        } catch (RequestException $exception) {
//            $response = $exception->getResponse();
//            $data = $this->parseResponseData($response->getBody()->getContents());
//            if (!empty($data->errors[0]->detail)) {
//                $exception_msg = $data->errors[0]->detail;
//            } else {
//                $exception_msg = $exception->getMessage();
//            }
//
//            throw new NetworkingServiceException($exception_msg, null, $response->getStatusCode());
            throw new NetworkingServiceException('Failed to load config for firewall #' . $firewallId . '', null, 502);
        }

        if ($response->getStatusCode() !== 200) {
            throw new NetworkingServiceException(
                'Failed to load config for firewall #' . $firewallId . '',
                null,
                $response->getStatusCode()
            );
        }

//        $data = $this->parseResponseData($response);
//        if ($data->id != $firewallId) {
//            throw new NetworkingServiceException('unexpected data in response');
//        }

        return 'CONFIG WOULD BE HERE';
    }

    /**
     * Makes a request to the Networking APIo.
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
            'debug' => false,
            'headers' => [
                'User-Agent' => 'service-' . env('APP_NAME') . '/1.0',
                'Accept' => 'application/json',
                'X-consumer-custom-id' => '0-0',
            ]
        ], $options));
    }

    /**
     * @param $json
     * @return mixed
     * @throws NetworkingServiceException
     */
    protected function parseResponseData($json)
    {
        $data = json_decode($json);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new NetworkingServiceException('failed to parse response data');
        }

        return $data;
    }
}
