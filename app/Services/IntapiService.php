<?php

namespace App\Services;

use App\Exceptions\V1\IntapiServiceException;
use GuzzleHttp\Exception\RequestException;
use Log;

class IntapiService
{
    protected $response;
    protected $responseData;

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
     * @throws IntapiServiceException
     */
    public function request($endpoint, $options = [])
    {
        $this->response = null;
        $this->responseData = null;

        $this->response = $this->client->request('POST', $endpoint, array_replace_recursive([
            'debug' => false,
            'headers' => [
                'Request-ID' => app('request')->header('Request-ID'),
                'User-Agent' => 'service-' . env('APP_NAME') . '/1.0',
                'Accept' => 'application/json',
                'Content-Type' => 'application/x-www-form-urlencoded',
            ]
        ], $options));

        //Check if there is a response body and parse response data
        if (!empty($this->response->getBody()->getContents())) {
            $this->response->getBody()->rewind();
            $this->responseData = $this->parseResponseData();
        }

        return $this->response;
    }

    /**
     * return last response
     * @return mixed
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Return last response data
     * @return mixed
     */
    public function getResponseData()
    {
        return $this->responseData;
    }

    /**
     * convert response to data object
     * @return mixed
     * @throws IntapiServiceException
     */
    protected function parseResponseData()
    {
        $content_type = $this->response->getHeaderLine('Content-Type');

        if (strpos($content_type, 'json') !== false) {
            $json = json_decode($this->response->getBody()->getContents());
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new IntapiServiceException('failed to parse response data: ' . json_last_error_msg());
            }

            return $json;
        } elseif (strpos($content_type, 'xml') !== false) {
            $xml = simplexml_load_string($this->response->getBody()->getContents());

            if ($xml === false) {
                $message = 'failed to parse response data';
                $errors = libxml_get_errors();
                if (!empty($errors)) {
                    $message .= ': ' . end($errors);
                }
                throw new IntapiServiceException($message);
            }

            // convert from simplexml to stdClass
            $json = json_decode(json_encode($xml));

            if (!is_bool($json->result)) {
                $json->result = ($json->result === 'TRUE');
            }

            return $json;
        }

        throw new IntapiServiceException('unknown response format: ' . $content_type);
    }

    public function getFriendlyError($error)
    {
        Log::info('IntAPI error: ' . $error);
        $pregResult = preg_match(
            '/^(?:.*): no available \'(.*)\' ip addresses(?:.*)(?: for \((.*)\))?$/',
            $error,
            $matches
        );
        switch ($error) {
            case ($pregResult == true):
                $ip_type = $matches[1];
//                $vlan = $matches[2];

                return 'No ' . $ip_type . ' IP addresses available';

            case (preg_match('/datastore has insufficient space/', $error) == true):
                $error_msg = 'Insufficient free storage available';

                $amount_left = filter_var($error, FILTER_SANITIZE_NUMBER_INT);
                if (is_numeric($amount_left)) {
                    $error_msg .= ', ' . max($amount_left, 0) . 'GB remaining';
                }

                return $error_msg;

            case (preg_match('/host has insufficient ram/', $error) == true):
                $error_msg = 'Insufficient free ram available';

                $amount_left = filter_var($error, FILTER_SANITIZE_NUMBER_INT);
                if (is_numeric($amount_left)) {
                    $error_msg .= ', ' . max($amount_left, 0) . 'GB remaining';
                }

                return $error_msg;

            case (preg_match('/^invalid tag key:/i', $error) == true):
                return $error;

            case (preg_match('/: no available firewall./i', $error) == true):
                return 'Unable to locate solution firewall, please contact support';

            default:
//                return $error;
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
            $this->request('/firewall/get-config', [
                'form_params' => [
                    'server_id' => $firewallId
                ]
            ]);
        } catch (RequestException $exception) {
            throw new IntapiServiceException('Failed to load config for firewall #' . $firewallId . '', null, 502);
        }

        if (!$this->responseData->result) {
            throw new IntapiServiceException(end($this->responseData->errorset));
        }

        return $this->responseData->config;
    }

    /**
     * Clone a virtual machine
     * @param $postData
     * @return mixed
     * @throws IntapiServiceException
     */
    public function cloneVM($postData)
    {
        $model = ['form_params' => $postData];

        try {
            $this->request('/automation/clone_ucs_vmware_vm', $model);
        } catch (RequestException $exception) {
            Log::critical($exception->getMessage());
            throw new IntapiServiceException('Failed to clone vm');
        }

        if (!$this->responseData->result) {
            Log::critical(end($this->responseData->errorset));
            throw new IntapiServiceException(end($this->responseData->errorset));
        }

        return $this->responseData->data->server_id;
    }

    /**
     * Schedule an automation request
     * @param $processName
     * @param $reference
     * @param $referenceId
     * @param null $data
     * @param null $queue
     * @param int $submittedById
     * @param string $submittedByType
     * @return mixed
     * @throws IntapiServiceException
     */
    public function automationRequest(
        $processName,
        $reference,
        $referenceId,
        $data = null,
        $queue = null,
        $submittedById = 0,
        $submittedByType = 'application'
    ) {
        if (strtolower($submittedByType) == 'application') {
            $submittedByType = 'API Client';
        }

        $post_data = [
            'process_system' => 'ucs_vmware',
            'process_name' => $processName,
            'reference_type' => $reference,
            'reference_id' => $referenceId,
            'submitted_by_type' => ucfirst($submittedByType),
            'submitted_by_id' => $submittedById,
            'data' => $data,
            'queue' => $queue
        ];

        $model = ['form_params' => $post_data];

        try {
            $this->request('/automation/request-create', $model);
        } catch (RequestException $exception) {
            throw new IntapiServiceException('Failed schedule automation request', null, 502);
        }

        if (!$this->responseData->result) {
            Log::critical(
                'Failed to schedule automation request:'
                . end($this->responseData->errorset)
                . ' data:' . serialize($post_data)
            );
            throw new IntapiServiceException(end($this->responseData->errorset));
        }

        return $this->responseData->automation_request->id;
    }
}
