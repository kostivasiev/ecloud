<?php

namespace App\Services\Kingpin\V1;

use App\Exceptions\V1\KingpinException;
use App\Models\V1\Solution;
use App\Template\PodTemplate;
use App\Template\SolutionTemplate;
use App\Template\Template;
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
        'Private',
        'GPU'
    ];

    protected $pod;

    public const KINGPIN_USER = 'kingpinapi';

    /**
     * KingpinService constructor.
     * @param $requestClient
     * @param $pod
     * @param null $environment Set an environment for the service, see $environmentOptions
     */
    public function __construct($requestClient, $pod, $environment = null)
    {
        $this->requestClient = $requestClient;

        $this->pod = $pod;

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

                    if (isset($disk->scsiCanonicalName)) {
                        $hdd->uuid = $disk->scsiCanonicalName;
                    } else {
                        $hdd->uuid = $disk->uuid;
                    }

                    $hdd->name = $disk->name;

                    $hdd->type = $disk->type;
                    $hdd->key = $disk->key;

                    $hdd->capacity = $disk->capacityGB;

                    $hdds[] = $hdd;
                }
            }
        } catch (TransferException $exception) {
            return false;
        }

        return $hdds;
    }

    /**
     * Get a VM's datastore
     * @param $vmId
     * @param null $solutionId
     * @return bool|object
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getVMDatastore($vmId, $solutionId = null)
    {
        $url = $this->generateV1URL($solutionId);
        $url .= 'vm/' . $vmId;

        $model = [
            'detailNetwork' => 'false',
            'detailDatastore' => 'true',
            'detailHost' => 'false',
        ];

        try {
            $this->makeRequest('GET', $url, $model);

            return $this->formatDatastore($this->responseData->datastore);
        } catch (TransferException $exception) {
            return false;
        }
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
     * Power On Virtual Machine
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
     * Power Off Virtual Machine
     * @param $vmId
     * @param null $solutionId
     * @return bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function powerOffVirtualMachine($vmId, $solutionId = null)
    {
        $url = $this->generateV1URL($solutionId);
        $url .= 'vm/' . $vmId . '/power';

        try {
            $this->makeRequest('DELETE', $url);
        } catch (TransferException $exception) {
            return false;
        }

        return true;
    }

    /**
     * Suspend / Pause Virtual Machine
     * @param $vmId
     * @param null $solutionId
     * @return bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function powerSuspend($vmId, $solutionId = null)
    {
        $url = $this->generateV1URL($solutionId);
        $url .= 'vm/' . $vmId . '/power/suspend';

        try {
            $this->makeRequest('PUT', $url);
        } catch (TransferException $exception) {
            return false;
        }

        return true;
    }


    /**
     * Retrieve Virtual Machine Solution Specific Templates
     * @param Solution $solution
     * @return array|bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getSolutionTemplates(Solution $solution)
    {
        $url = $this->generateV1URL($solution->getKey());
        $url .= 'template';

        $templates = [];
        try {
            $this->makeRequest('GET', $url);
            if (!empty($this->responseData) && is_array($this->responseData)) {
                foreach ($this->responseData as $template) {
                    $solutionTemplate = new SolutionTemplate($template, $this->pod, $solution);
                    $templates[] = $solutionTemplate;
                }
            }
        } catch (TransferException $exception) {
            return false;
        }

        return $templates;
    }

    /**
     * Get a single solution template
     * @param Solution $solution
     * @param $templateName
     * @return SolutionTemplate|array|bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getSolutionTemplate(Solution $solution, $templateName)
    {
        $url = $this->generateV1URL($solution->getKey());
        $url .= 'template/' . $templateName;

        try {
            $this->makeRequest('GET', $url);

            if (empty($this->responseData)) {
                Log::info(
                    'Failed to load solution template from Kingpin by name: no response data from Kingpin',
                    [
                        'solution_id' => $solution->getKey(),
                        'requested_template_name' => $templateName,
                        'url' => $url,
                    ]
                );
                return false;
            }

            $solutionTemplate = new SolutionTemplate($this->responseData, $this->pod, $solution);

            return $solutionTemplate;
        } catch (TransferException $exception) {
            Log::info(
                'Failed to load solution template from Kingpin by name',
                [
                    'solution_id' => $solution->getKey(),
                    'requested_template_name' => $templateName,
                    'url' => $url,
                ]
            );
            return false;
        }
    }


    /**
     * Get the Virtual Machine 'System Templates' for a datacentre/pod
     * @return array|bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getPodTemplates()
    {
        $url = $this->generateV1URL(null, true);
        $url .= 'system/template';

        $templates = [];
        try {
            $this->makeRequest('GET', $url);

            if (!empty($this->responseData) && is_array($this->responseData)) {
                foreach ($this->responseData as $template) {
                    $Template = new PodTemplate($template, $this->pod);

                    if (empty($Template->serverLicense->name)) {
                        continue; // no license name, means license not available to customers
                    }

                    $templates[] = $Template;
                }
            }
        } catch (TransferException $exception) {
            return false;
        }

        return $templates;
    }

    /**
     * Rename a Solution Template
     * @param $solutionId
     * @param $templateName
     * @param $newTemplateName
     * @return bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function renameSolutionTemplate($solutionId, $templateName, $newTemplateName)
    {
        $url = $this->generateV1URL($solutionId);
        $url .= 'template/' . $templateName . '/name';

        $model = [
            'newTemplateName' => $newTemplateName,
        ];

        try {
            $this->makeRequest('POST', $url, $model);
        } catch (TransferException $exception) {
            return false;
        }

        return true;
    }

    /**
     * Perform a cluster rescan
     * @param $solutionId
     * @return bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function clusterRescan($solutionId)
    {
        $url = $this->generateV1URL($solutionId);
        $url .= 'cluster/rescan';

        try {
            $this->makeRequest('PUT', $url);
        } catch (TransferException $exception) {
            return false;
        }

        return true;
    }

    /**
     * Create datastore
     * @param $solutionId
     * @param $datastoreName
     * @param $wwn
     * @return bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function createDatastore($solutionId, $datastoreName, $wwn)
    {
        $url = $this->generateV1URL($solutionId);
        $url .= 'datastore';

        $model = [
            'datastoreName' => $datastoreName,
            'wwn' => $wwn
        ];

        try {
            $this->makeRequest('POST', $url, $model);
        } catch (TransferException $exception) {
            return false;
        }

        return true;
    }

    /**
     * Expand a datastore
     * @param $solutionId
     * @param $datastoreName
     * @return bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function expandDatastore($solutionId, $datastoreName)
    {
        $url = $this->generateV1URL($solutionId);
        $url .= 'datastore/' . $datastoreName . '/expand';

        try {
            $this->makeRequest('PUT', $url);
        } catch (TransferException $exception) {
            return false;
        }

        return true;
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
                $this->generateV1URL($solutionId) . 'host/' . $eth0_mac . ''
            );
        } catch (TransferException $exception) {
//            throw new KingpinException($exception->getMessage());
            throw new KingpinException('unable to query host');
        }

        if (!is_object($this->responseData)) {
            throw new KingpinException('failed to parse host response');
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
        return (object)[
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
     * Get hosts for Solution
     * @param $solutionId
     * @param bool $detailVM
     * @return array
     * @throws KingpinException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getHostsForSolution($solutionId, $detailVM = false)
    {
        try {
            $this->makeRequest(
                'GET',
                $this->generateV1URL($solutionId) . 'host?detailVM=' . ($detailVM ? 'true' : 'false')
            );
        } catch (TransferException $exception) {
            throw new KingpinException('unable to query hosts');
        }

        if (!is_array($this->responseData)) {
            throw new KingpinException('failed to parse hosts query response');
        }

        $hosts = [];

        foreach ($this->responseData as $host) {
            $hosts[] = $this->formatHost($host);
        }

        return $hosts;
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
                $this->generateV1URL($solutionId) . 'datastore/' . $datastoreName . ''
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
     * Returns an array of datastores for the solution
     * @param $solutionId
     * @return null
     * @throws KingpinException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getDatastores($solutionId)
    {
        try {
            $this->makeRequest(
                'GET',
                $this->generateV1URL($solutionId) . 'datastore'
            );
        } catch (TransferException $exception) {
            throw new KingpinException($exception->getMessage());
        }

        $datastores = [];
        foreach ($this->responseData as $datastore) {
            $datastores[] = $this->formatDatastore($datastore);
        }

        return $datastores;
    }

    /**
     * Returns an array of DRS rules for the solution
     * @param $solution
     * @return null
     * @throws KingpinException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getDrsRulesForSolution(Solution $solution)
    {
        try {
            $this->makeRequest(
                'GET',
                $this->generateV1URL($solution->getKey()) . 'cluster/drs/rule'
            );
        } catch (TransferException $exception) {
            throw new KingpinException($exception->getMessage());
        }

        return $this->responseData;
    }

    /**
     * format datastore object to standard response
     * @param $vmwareObject
     * @return object
     */
    protected function formatDatastore($vmwareObject)
    {
        return (object)[
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
            case 'GPU':
                return '/api/v1/burstgpu/solution/' . $solutionId . '/';
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

        if (is_null($response)) {
            Log::debug('No response body from Kingpin request, service may be unavailable.');
            return true;
        }

        if ($response->getStatusCode() == 401) {
            Log::debug(
                'Connection attempt to Kingpin returned an Unauthorized response,'
                . ' check datacentre and VMWare API URL for VM are correct and VCE server details for the Pod are valid.'
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

        // Only set JSON if we're sending data in the request
        if (empty($this->requestData) === false) {
            $requestOptions['json'] = $this->requestData;
        }

        // If we pass $data with a GET request add it to the query string i.e. ?key=val
        if ($method === 'GET') {
            if (!empty($data)) {
                $requestOptions['query'] = $data;
            }
        }

        try {
            $this->response = $this->requestClient->request(
                $method,
                $this->requestUrl,
                array_merge($this->requestClient->getConfig('defaults'), $requestOptions ?? [])
            );

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

    /**
     * @param $vmId
     * @param null $solutionId
     * @return array|bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function consoleSession($vmId, $solutionId = null)
    {
        try {
            $this->makeRequest('POST', $this->generateV1URL($solutionId) . 'vm/' . (int)$vmId . '/console/session', []);
        } catch (TransferException $exception) {
            return false;
        }
        return [
            'host' => $this->responseData->host ?? '',
            'ticket' => $this->responseData->ticket ?? '',
        ];
    }
}
