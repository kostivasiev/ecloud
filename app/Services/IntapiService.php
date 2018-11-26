<?php

namespace App\Services;

use GuzzleHttp\Exception\RequestException;

use App\Exceptions\V1\IntapiServiceException;
use Log;

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
            throw new IntapiServiceException('Failed to load config for firewall #' . $firewallId . '', null, 502);
        }

        $data = $this->parseResponseData($response->getBody()->getContents());

        if (!$data->result) {
            throw new IntapiServiceException(end($data->errorset));
        }

        return $data->config;
    }

    /**
     * Schedule an automation request
     * @param $processName
     * @param $reference
     * @param $referenceId
     * @param null $data
     * @param null $queue
     * @return mixed
     * @throws IntapiServiceException
     */
    public function automationRequest($processName, $reference, $referenceId, $data = null, $queue = null)
    {
        $post_data = [
            'process_system' => 'ucs_vmware',
            'process_name' => $processName,
            'reference_type' => $reference,
            'reference_id' => $referenceId,
            'submitted_by_type' => 'API Client',
            'submitted_by_id' => 0,
            'data' => $data,
            'queue' => $queue
        ];

        $model = ['form_params' => $post_data];

        try {
            $response = $this->request('POST', '/automation/request-create', $model);
        } catch (RequestException $exception) {
            throw new IntapiServiceException('Failed schedule automation request', null, 502);
        }

        $response = $this->parseResponseData($response->getBody()->getContents());

        if (!$response->result) {
            Log::critical(
                'Failed to schedule automation request:'
                . end($response->errorset)
                . ' data:' . serialize($post_data)
            );
            throw new IntapiServiceException(end($response->errorset));
        }

        return $response->automation_request->id;
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
                'User-Agent' => 'service-' . env('APP_NAME') . '/1.0',
                'Accept' => 'application/json',
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
