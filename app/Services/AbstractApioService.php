<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\TransferException;
use Log;

abstract class AbstractApioService
{
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
     * The last error returned from Artisan.
     * @var null
     */
    protected $lastError = null;

    /**
     * @var string
     */
    protected $serviceName = '';

    /**
     * AbstractApioService constructor.
     * @param $client
     * @throws \Exception
     */
    public function __construct(Client $client)
    {
        $this->client = $client;

        if (empty($this->serviceName)) {
            throw new \Exception('Service Not Defined');
        }

        $this->headers = [
            'Request-ID' => app('request')->header('Request-ID'),
            'User-Agent' => 'service-' . env('APP_NAME') . '/1.0',
            'Accept' => 'application/json',
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => $this->serviceName . '.read' . ', ' . $this->serviceName . '.write'
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
     * Returns the last error returned from a query
     * @return null
     */
    public function getLastError()
    {
        return $this->lastError;
    }

    /**
     * Send the HTTP request
     * @param       $method
     * @param $endpoint
     * @param null $data - Deploy payload (JSON or query string depending on the verb)
     * @param array $options - Any other options we want to pass to Guzzle
     * @return bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function makeRequest($method, $endpoint, $data = null, $options = [])
    {
        if (!empty($data)) {
            // If HTTP GET append data payload to the query string i.e. ?key=val, otherwise add it as json.
            $property = ($method == 'GET') ? 'query' : 'json';
            $options[$property] = $data;
        }

        try {
            return $this->response = $this->client->request(
                $method,
                $endpoint,
                array_merge_recursive([
                    'debug' => false,
                    'headers' => $this->headers
                ], $options)
            );
        } catch (TransferException $exception) {
            $this->handleException($exception, $method, $endpoint);
            throw $exception;
        }
    }

    /**
     * Handles instances of ServerException
     * @param TransferException $exception
     * @param $method
     * @param $endpoint
     * @return bool
     */
    protected function handleException(TransferException $exception, $method, $endpoint): bool
    {
        // Faff around to get the details because Guzzle is a PITA & truncates our exception message...
        $responseBody = null;
        $this->response = $exception->getResponse();
        if (!is_null($this->response)) {
            $stream = $this->response->getBody();
            $stream->rewind();
            $responseBody = $stream->getContents(); // returns all the contents
        }

        $this->lastError = $exception->getMessage();

        $decodedResponseBody = json_decode($responseBody, true);
        $logMessage = 'Failed to make request to ' . $method . ' ' . $endpoint;

        // Set a default set of data to log
        $exceptionData = [
            'response' => $responseBody
        ];

        // If we can't JSON-decode it, there's not much we can do with the response
        if (json_last_error() === JSON_ERROR_NONE) {
            $exceptionData = $decodedResponseBody;
            unset($exceptionData['debug']['trace']);
        }

        Log::critical($logMessage, $exceptionData);

        if (is_null($this->response)) {
            Log::debug('No response body from request, service may be unavailable.');
            return true;
        }

        if ($this->response->getStatusCode() == 401) {
            Log::debug(
                'Connection attempt to service returned an Unauthorized response'
            );
        }

        return true;
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
