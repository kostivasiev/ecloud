<?php

namespace App\Kingpin\V1;

use App\Exceptions\V1\ArtisanException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Exception\TransferException;
use Log;
use Psr\Http\Message\ResponseInterface;

/**
 * Mess detector suppress coupling between objects warning
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class KingpinService
{
    /**
     * Request client
     * @var \GuzzleHttp\Client $requestClient
     */
    protected $requestClient;

    /**
     * Request data
     * @var mixed[] $data
     */
    protected $requestData;

    /**
     * Request method
     * @var string $requestMethod
     */
    protected $requestMethod;

    /**
     * URL of the most recent request
     * @var string $url
     */
    protected $requestUrl;

    /**
     * Response received from the request
     * @var ResponseInterface $response
     */
    protected $response;

    /**
     * JSON decoded Response body data
     * @var null
     */
    protected $responseData = null;

    /**
     * The last error returned from Artisan.
     * @var null
     */
    protected $lastError = null;

    /**
     * Determine the ecloud environment
     * Hybrid / Public / Burst
     * @var string
     */
    protected $environment = 'Hybrid';

    /**
     * Allowed environment options
     * @var array
     */
    protected $environmentOptions = [
        'Public',
        'Hybrid',
        'Burst',
        'Private'
    ];

    /**
     * KingpinService constructor.
     * @param $requestClient
     * @param null $environment Set an environment for the service, see $environmentOptions
     */
    public function __construct($requestClient, $environment = null)
    {
        $this->requestClient = $requestClient;

        if (!empty($environment)) {
            return $this->setEnvironment($environment);
        }
        return true;
    }

    /**
     * Return the request client
     * @return object
     */
    public function getRequestClient()
    {
        return $this->requestClient;
    }

    /**
     * Returns the request data
     * @return mixed[]
     */
    public function getRequestData(): array
    {
        return $this->requestData;
    }

    /**
     * Returns the request method
     * @return string
     */
    public function getRequestMethod(): string
    {
        return $this->requestMethod;
    }

    /**
     * Return URL of the most recent request
     * @return string
     */
    public function getRequestUrl(): string
    {
        return $this->requestUrl;
    }

    /**
     * Return response of the most recent request
     * @return ResponseInterface
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Returns JSON decoded response data for the most recent request
     * @return null
     */
    public function getResponseData()
    {
        return $this->responseData;
    }

    /**
     * Returns the last error returned from Artisan
     * @return null
     */
    public function getLastError()
    {
        return $this->lastError;
    }

    /**
     * Set the environment option for the service
     * @param $environment
     * @return bool
     */
    public function setEnvironment($environment)
    {
        if (!in_array($environment, $this->environmentOptions)) {
            $this->lastError = "Failed to set environment. Invalid option '$environment'.";
            return false;
        }

        $this->environment = $environment;
        return true;
    }

    /**
     * Check whether the VM is online
     * @param $solutionId (optional, for Hybrid/Burst - not needed for Public)
     * @param $vmId
     * @return bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Exception
     */
    public function checkVMOnline($vmId, $solutionId = null)
    {
        $url = $this->generateV1URL();
        if (in_array($this->environment, ['Hybrid', 'Burst'])) {
            $url .= 'solution/' . $solutionId . '/';
        }
        $url .= 'vm/' . $vmId;

        $model = [
            'detailNetwork' => 'false',
            'detailDatastore' => 'false',
            'detailHost' => 'false',
        ];

        try {
            $this->makeRequest('GET', $url, $model);
        } catch (TransferException $exception) {
            return false;
        }

        return $this->responseData->status == 'poweredOn';
    }


    /**
     * Get the current status of the server's VMWare tool
     * @param $solutionId
     * @param $vmId
     * @return bool|string
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Exception
     */
    public function vmwareToolsStatus($solutionId, $vmId)
    {
        $url = $this->generateV1URL();
        if (in_array($this->environment, ['Hybrid', 'Burst'])) {
            $url .= 'solution/' . $solutionId . '/';
        }
        $url .= 'vm/' . $vmId;

        $model = [
            'detailNetwork' => 'false',
            'detailDatastore' => 'false',
            'detailHost' => 'false',
        ];

        try {
            $this->makeRequest('GET', $url, $model);
        } catch (TransferException $exception) {
            return false;
        }

        $toolsStatus = $this->responseData->toolsStatus;

        if ($toolsStatus == 'toolsOld') {
            return 'toolsOk';
        }

        return $toolsStatus;
    }

    /**
     * Get active HDD's for the VM
     * @param $solutionId
     * @param $vmId
     * @return array|bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Exception
     */
    public function getActiveHDDs($solutionId, $vmId)
    {
        $url = $this->generateV1URL();
        if (in_array($this->environment, ['Hybrid', 'Burst'])) {
            $url .= 'solution/' . $solutionId . '/';
        }
        $url .= 'vm/' . $vmId;

        $model = [
            'detailNetwork' => 'false',
            'detailDatastore' => 'false',
            'detailHost' => 'false',
        ];

        try {
            $this->makeRequest('GET', $url, $model);

            $hdds = [];

            if (!empty($this->responseData->disks)) {
                foreach ($this->responseData->disks as $disk) {
                    $hdd = new \StdClass();
                    $hdd->name = $disk->name;
                    $hdd->capacity = $disk->capacityGB;
                    $hdd->type = $disk->type;
                    $hdd->uuid = $disk->uuid;
                    $hdd->key = $disk->key;
                    $hdds[] = $hdd;
                }
            }
        } catch (TransferException $exception) {
            return false;
        }

        return $hdds;
    }

    /**
     * Generates the base URL
     * @return string
     * @throws \Exception
     */
    protected function generateV1URL(): string
    {
        switch ($this->environment) {
            case 'Public':
                return 'api/v1/public/';
                break;
            case 'Burst':
                return 'api/v1/burst';
                break;
            case 'Hybrid':
            default:
                return 'api/v1/';
        }
    }

    /**
     * Handles instances of ServerException
     * @param TransferException $exception
     * @return bool
     */
    protected function handleException(TransferException $exception): bool
    {
        // Faff around to get the details because Guzzle is a PITA & truncates our exception message...
        $responseBody = null;
        $response = $exception->getResponse();
        if (!is_null($response)) {
            $stream = $response->getBody();
            $stream->rewind();
            $responseBody = $stream->getContents(); // returns all the contents
        }

        $decodedResponseBody = json_decode($responseBody, true);
        $logMessage = 'Failed to make request to ' . $this->getRequestMethod() . ' ' . $this->getRequestUrl();

        // Set a default set of data to log
        $exceptionData = [
            'environment' => $this->environment,
            'response' => $responseBody
        ];

        // If we can't JSON-decode it, there's not much we can do with the response
        if (json_last_error() === JSON_ERROR_NONE) {
            $exceptionData = $decodedResponseBody;

            // We don't care about logging the stack trace
            unset($exceptionData['StackTrace']);
        }

        Log::critical($logMessage, $exceptionData);

        if (is_null($response)) {
            Log::debug('No response body from Kingpin request, service may be unavailable.');
        }

        return true;
    }

    /**
     * Make a request
     * @param string $method
     * @param string $url
     * @param array|null $data
     * @return ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Exception
     */
    protected function makeRequest(string $method, string $url, array $data = array())
    {
        $this->requestData = $data;
        $this->requestMethod = $method;
        $this->requestUrl = $url;
        $this->response = null;
        $this->responseData = null;

        // Authentication options
        $requestOptions = [
            'auth' => [env('VMWARE_API_USER'), env('VMWARE_API_PASS')]
        ];

        // Only set JSON if we're sending data in the request
        if (empty($this->requestData) === false) {
            $requestOptions['json'] = $this->requestData;
        }
        try {
            $this->response = $this->requestClient->request($method, $this->requestUrl, $requestOptions);
            // check if there is a response body
            $this->responseData = json_decode($this->response->getBody()->getContents());
        } catch (TransferException $exception) {
            $this->handleException($exception);
            $this->response = $exception->getResponse();

            $this->lastError = $exception->getMessage();
            //TODO: Move to handleException()
            // Faff around to get the details because Guzzle is a PITA & truncates our exception message...
            $message = null;
            $response = $exception->getResponse();
            if (!is_null($response)) {
                $stream = $response->getBody();
                $stream->rewind();
                $message = $stream->getContents(); // returns all the contents
            }

            if (preg_match('/"ExceptionMessage":"([^"]+)/', $message, $matches)) {
                $this->lastError = $matches[1];
            }

            throw $exception;
        }

        return $this->response;
    }
}
