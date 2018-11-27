<?php

namespace App\Kingpin\V1;

use App\Exceptions\V1\KingpinException;
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
        $url = $this->generateV1URL($solutionId);
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
    public function vmwareToolsStatus($vmId, $solutionId = null)
    {
        $url = $this->generateV1URL($solutionId);
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
    public function getActiveHDDs($vmId, $solutionId = null)
    {
        $url = $this->generateV1URL($solutionId);
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
     * @param $vmId
     * @param null $solutionId
     * @return bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function shutDownVirtualMachine($vmId, $solutionId = null)
    {
        $url = $this->generateV1URL($solutionId);
        $url .= 'vm/' . $vmId . '/power/shutdown';

        try {
            $this->makeRequest('PUT', $url);
        } catch (TransferException $exception) {
            return false;
        }

        return true;
    }

    /**
     * @param $vmId
     * @param null $solutionId
     * @return bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function powerOnVirtualMachine($vmId, $solutionId = null)
    {
        $url = $this->generateV1URL($solutionId);
        $url .= 'vm/' . $vmId . '/power';

        try {
            $this->makeRequest('POST', $url);
        } catch (TransferException $exception) {
            return false;
        }

        return true;
    }


    /**
     * Retrieve Virtual Machine Solution Specific Templates
     * @param $solutionId
     * @return array|bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getSolutionTemplates($solutionId)
    {
        $url = $this->generateV1URL($solutionId);
        $url .= 'template';

        $templates = [];
        try {
            $this->makeRequest('GET', $url);
            if (!empty($this->responseData) && is_array($this->responseData)) {
                $templates = $this->processTemplateData($this->responseData);
            }
        } catch (TransferException $exception) {
            return false;
        }

        return $templates;
    }


    /**
     * Get the Virtual Machine 'System Templates' for a datacentre/pod
     * @return array|bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getSystemTemplates()
    {
        $url = $this->generateV1URL(null, true);
        $url .= 'system/template';

        $templates = [];
        try {
            $this->makeRequest('GET', $url);

            if (!empty($this->responseData) && is_array($this->responseData)) {
                $templates = $this->processTemplateData($this->responseData);
            }
        } catch (TransferException $exception) {
            return false;
        }

        return $templates;
    }

    /**
     * Get Host by its MAC address
     * @param $solutionId
     * @param $eth0_mac
     * @return object
     * @throws KingpinException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getHostByMac($solutionId, $eth0_mac)
    {
        try {
            $this->makeRequest(
                'GET',
                $this->generateV1URL($solutionId) . 'host/'.$eth0_mac.''
            );
        } catch (TransferException $exception) {
//            throw new KingpinException($exception->getMessage());
            throw new KingpinException('unable to query host');
        }

        if (!is_object($this->responseData)) {
            throw new KingpinException('failed to parse host response');
        }

        if ($this->responseData->macAddress != $eth0_mac) {
            throw new KingpinException('unexpected host response');
        }

        return $this->formatHost($this->responseData);
    }

    /**
     * format host object to standard response
     * @param $vmwareObject
     * @return object
     */
    protected function formatHost($vmwareObject)
    {
        return (object) [
            'uuid' => $vmwareObject->modelRef,
            'name' => $vmwareObject->name,
            'macAddress' => $vmwareObject->macAddress,
            'powerStatus' => $vmwareObject->powerState,
            'networkStatus' => $vmwareObject->connectionState,
            'vms' => $vmwareObject->vms,
            'stats' => $vmwareObject->stats,
        ];
    }

    /**
     * return vmware datastore
     * @param $solutionId
     * @param $datastoreName
     * @return null
     * @throws KingpinException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getDatastore($solutionId, $datastoreName)
    {
        try {
            $this->makeRequest(
                'GET',
                $this->generateV1URL($solutionId) . 'datastore/'.$datastoreName.''
            );
        } catch (TransferException $exception) {
            throw new KingpinException($exception->getMessage());
        }

        if (!is_object($this->responseData)) {
            throw new KingpinException('failed to load datastore');
        }

        if ($this->responseData->name != $datastoreName) {
            throw new KingpinException('unexpected datastore response');
        }

        return $this->formatDatastore($this->responseData);
    }

    /**
     * format datastore object to standard response
     * @param $vmwareObject
     * @return object
     */
    protected function formatDatastore($vmwareObject)
    {
        return (object) [
            'uuid' => $vmwareObject->modelRef,
            'name' => $vmwareObject->name,
            'type' => $vmwareObject->type,
            'capacity' => $vmwareObject->capacityGB,
            'freeSpace' => $vmwareObject->freeSpaceGB,
            'uncommitted' => $vmwareObject->uncommittedGB,
            'provisioned' => $vmwareObject->provisionedGB,
            'available' => $vmwareObject->availableGB,
            'used' => $vmwareObject->usedGB,
        ];
    }

    /**
     * Process and format template data from getSystemTemplates() & getSolutionTemplates()
     * @param $templates
     * @return array
     */
    protected function processTemplateData($templates)
    {
        $forattedTemplates = [];
        foreach ($templates as $template) {
            $temp_template = new \stdClass();
            $temp_template->name = (string)$template->name;
            $temp_template->size_gb = (string)$template->capacityGB;
            $temp_template->guest_os = (string)$template->guestOS;
            $temp_template->actual_os = trim((string)$template->actualOS);
            $temp_template->cpu = intval($template->numCPU);
            $temp_template->ram = intval($template->ramGB);

            $hard_drives = array();
            foreach ($template->disks as $hard_drive) {
                $hdd = new \stdClass();
                $hdd->name = (string)$hard_drive->name;
                $hdd->capacitygb = intval($hard_drive->capacityGB);
                $hard_drives[] = $hdd;
            }

            $temp_template->hard_drives = $hard_drives;

            $forattedTemplates[] = $temp_template;
        }

        return $forattedTemplates;
    }


    /**
     * Generates the base URL
     * @param null $solutionId
     * @param bool $ignoreEnvironment
     * @return string
     */
    protected function generateV1URL($solutionId = null, $ignoreEnvironment = false)
    {
        if ($ignoreEnvironment) {
            return 'api/v1/';
        }

        switch ($this->environment) {
            case 'Public':
                return 'api/v1/public/';
                break;
            case 'Burst':
                return 'api/v1/burst/solution/' . $solutionId . '/';
                break;
            case 'Hybrid':
            default:
                return 'api/v1/solution/' . $solutionId . '/';
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
            return true;
        }

        if ($response->getStatusCode() == 401) {
            Log::debug(
                'Connection attempt to Kingpin returned an Unauthorized response,'
                . ' check datacentre and VMWare API URL for VM are correct.'
            );
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
