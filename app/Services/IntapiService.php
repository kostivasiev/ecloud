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
     * Makes a request to the UKFast Internal Api.
     *
     * @param $endpoint
     * @param array $options
     *
     * @return Response
     */
    public function request($endpoint, $options = [])
    {
        return $this->response = $this->client->request('POST', $endpoint, array_replace_recursive([
            'debug' => false,
            'headers' => [
                'User-Agent' => 'service-' . env('APP_NAME') . '/1.0',
                'Accept' => 'application/json',
                'Content-Type' => 'application/x-www-form-urlencoded',
            ]
        ], $options));
    }

    /**
     * convert response to data object
     * @return mixed
     * @throws IntapiServiceException
     */
    public function parseResponseData()
    {
        $content_type = $this->response->getHeaderLine('Content-Type');

        if (strpos($content_type, 'json') !== false) {
            $json = json_decode($this->response->getBody()->getContents());
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new IntapiServiceException('failed to parse response data: '.json_last_error_msg());
            }

            return $json;
        } elseif (strpos($content_type, 'xml') !== false) {
            $xml = simplexml_load_string($this->response->getBody()->getContents());
            if ($xml === false) {
                throw new IntapiServiceException('failed to parse response data: '.end(libxml_get_errors()));
            }

            // convert from simplexml to stdClass
            $json = json_decode(json_encode($xml));

            if (!is_bool($json->result)) {
                $json->result = ($json->result === 'TRUE');
            }

            return $json;
        }

        throw new IntapiServiceException('unknown response format: '.$content_type);
    }

    public function getFriendlyError($error)
    {
        switch ($error) {
            case (preg_match('/no available.*ip addresses/', $error) == true):
                $ip_type = 'external';
                if (strpos($error, 'internal') !== false) {
                    $ip_type = 'internal';
                }
                return 'No ' . $ip_type . ' IP addresses available';

            case (preg_match('/datastore has insufficient space/', $error) == true):
                $error_msg = 'Insufficient free space on selected datastore';

                $amount_left = filter_var($error, FILTER_SANITIZE_NUMBER_INT);
                if (is_numeric($amount_left)) {
                    $error_msg .= ', ' . $amount_left . 'GB remaining';
                }

                return $error_msg;

            default:
                return 'Please try again in a few moments or contact our support team if the fault persists.';
        }
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
            $response = $this->request('/firewall/get-config', [
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
    public function automationRequest($processName, $reference, $referenceId, $data = null, $queue = null, $applicationId = 0)
    {
        $post_data = [
            'process_system' => 'ucs_vmware',
            'process_name' => $processName,
            'reference_type' => $reference,
            'reference_id' => $referenceId,
            'submitted_by_type' => 'API Client',
            'submitted_by_id' => $applicationId,
            'data' => $data,
            'queue' => $queue
        ];

        $model = ['form_params' => $post_data];

        try {
            $response = $this->request('/automation/request-create', $model);
        } catch (RequestException $exception) {
            throw new IntapiServiceException('Failed schedule automation request', null, 502);
        }

        $data = $this->parseResponseData($response->getBody()->getContents());

        if (!$data->result) {
            Log::critical(
                'Failed to schedule automation request:'
                . end($data->errorset)
                . ' data:' . serialize($post_data)
            );
            throw new IntapiServiceException(end($data->errorset));
        }

        return $data->automation_request->id;
    }
}
