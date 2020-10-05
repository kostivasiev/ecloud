<?php

namespace App\Services\Artisan\V1;

use App\Exceptions\V1\ArtisanException;
use GuzzleHttp\Exception\TransferException;
use Log;
use Psr\Http\Message\ResponseInterface;

/**
 * Mess detector suppress coupling between objects warning
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ArtisanService
{
    /**
     * Artisan API Authentication password
     * @var string $authPassword
     */
    private $authPassword;

    /**
     * Artisan API Authentication username
     * @var string $authUsername
     */
    private $authUsername;

    /**
     * SAN Authentication username
     * @var string $sanUsername
     */
    private $sanUsername;

    /**
     * SAN Authentication username
     * @var string $sanUsername
     */
    private $sanPassword;

    /**
     * SAN name (server netbios name)
     * @var string $sanName
     */
    private $sanName;

    /**
     * Solution ID
     * @var null
     */
    private $solutionId;

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

    public const ARTISAN_API_USER = 'artisanapi';

    /**
     * FirewallService constructor.
     * @param $requestClient
     * @param $sanName
     * @param null $solutionId
     */
    public function __construct($requestClient, $sanName, $solutionId)
    {
        $this->requestClient = $requestClient;
        $this->sanName = $sanName;
        $this->solutionId = $solutionId;
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
     * Set the authentication values
     * @param string $username
     * @param string $password
     * @return $this
     */
    public function setAPICredentials(string $username, string $password)
    {
        $this->authUsername = $username;
        $this->authPassword = $password;

        return $this;
    }

    /**
     * Set SAN authentication values
     * @param string $username
     * @param string $password
     * @return $this
     */
    public function setSANCredentials(string $username, string $password)
    {
        $this->sanUsername = $username;
        $this->sanPassword = $password;

        return $this;
    }

    /**
     * Generates the base URL
     * @return string
     */
    protected function generateV1URL(): string
    {
        return 'api/v1/san/' . $this->sanName . '/solution/' . $this->solutionId;
    }


    /**
     * Get a Host Set
     * @param $hostSetName
     * @return bool|\stdClass
     * @throws ArtisanException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getHostSet($hostSetName)
    {
        $baseUrl = $this->generateV1URL();
        $url = $baseUrl . '/hostset/' . $hostSetName;

        try {
            $this->makeRequest('GET', $url);

            //Return the new host set
            $hostSet = new \stdClass();
            $hostSet->name = $this->responseData->name;
            $hostSet->hosts = $this->responseData->hosts;
            if (!is_array($hostSet->hosts)) {
                $hostSet->hosts = [];
            }
        } catch (TransferException $exception) {
            return false;
        }

        return $hostSet;
    }

    /**
     * Get a volume set
     * @param $volumeSetName
     * @return bool|\stdClass
     * @throws ArtisanException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getVolumeSet($volumeSetName)
    {
        $baseUrl = $this->generateV1URL();
        $url = $baseUrl . '/volumeset/' . $volumeSetName;

        try {
            $this->makeRequest('GET', $url);

            //Return the new host set
            $volumeSet = new \stdClass();
            $volumeSet->name = $this->responseData->name;
            $volumeSet->volumes = $this->responseData->volumes;
            if (!is_array($volumeSet->volumes)) {
                $volumeSet->volumes = [];
            }
        } catch (TransferException $exception) {
            return false;
        }

        return $volumeSet;
    }


    /**
     * Retrieve exports for volume sets to host sets
     * @param $volumeSetName
     * @return bool|\array
     * @throws ArtisanException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getVolumeSetExports($volumeSetName)
    {
        $baseUrl = $this->generateV1URL();
        $url = $baseUrl . '/volumeset/' . $volumeSetName . '/export';

        try {
            $this->makeRequest('GET', $url);

            $hostSets = [];
            // Returns just the host sets (not the hosts in the set)
            foreach ($this->responseData as $hostSet) {
                $hostSets[] = $hostSet->name;
            }
        } catch (TransferException $exception) {
            return false;
        }

        return $hostSets;
    }


    /**
     * Set the maximum IOPS on a volume set
     * @param string $volumeSetName - volume set internal name
     * @param $maximumIOPS
     * @return bool
     * @throws ArtisanException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function setIOPS($volumeSetName, $maximumIOPS)
    {
        $baseUrl = $this->generateV1URL();
        $url = $baseUrl . '/volumeset/' . $volumeSetName . '/maxiops';

        $model = array(
            'maximumIOPS' => $maximumIOPS
        );

        try {
            $this->makeRequest('PUT', $url, $model);
        } catch (TransferException $exception) {
            return false;
        }

        return true;
    }


    /**
     * Retrieve exports for volume sets to host sets
     * @param $hostSetName
     * @param $hostName
     * @return bool|\stdClass
     * @throws ArtisanException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function addHostToHostSet($hostSetName, $hostName)
    {
        $baseUrl = $this->generateV1URL();
        $url = $baseUrl . '/hostset/' . $hostSetName . '/member';

        $model = array(
            'hostName' => $hostName
        );

        try {
            $this->makeRequest('PUT', $url, $model);
        } catch (TransferException $exception) {
            return false;
        }

        return true;
    }

    /**
     * Retrieve exports for volume sets to host sets
     * @param $hostSetName
     * @param $hostName
     * @return bool|\stdClass
     * @throws ArtisanException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function removeHostFromHostSet($hostSetName, $hostName)
    {
        $baseUrl = $this->generateV1URL();
        $url = $baseUrl . '/hostset/' . $hostSetName . '/member/' . $hostName;

        try {
            $this->makeRequest('DELETE', $url);
        } catch (TransferException $exception) {
            return false;
        }

        return true;
    }


    /**
     * Delete a host
     * @param $hostName
     * @return bool
     * @throws ArtisanException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function removeHost($hostName)
    {
        $baseUrl = $this->generateV1URL();
        $url = $baseUrl . '/host/' . $hostName;

        try {
            $this->makeRequest('DELETE', $url);
        } catch (TransferException $exception) {
            return false;
        }

        return true;
    }

    /**
     * Add a volume to a volume set
     * @param $volumeSetName
     * @param $volumeName
     * @return bool|\stdClass
     * @throws ArtisanException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function addVolumeToVolumeSet($volumeSetName, $volumeName)
    {
        $baseUrl = $this->generateV1URL();
        $url = $baseUrl . '/volumeset/' . $volumeSetName . '/member';

        $model = array(
            'volumeName' => $volumeName
        );

        try {
            $this->makeRequest('PUT', $url, $model);
        } catch (TransferException $exception) {
            return false;
        }

        return true;
    }

    /**
     * Expand a volume
     * @param $volumeName
     * @param $newSizeMib
     * @return bool
     * @throws ArtisanException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function expandVolume($volumeName, $newSizeMib)
    {
        $baseUrl = $this->generateV1URL();
        $url = $baseUrl . '/volume/' . $volumeName . '/expand';

        $model = array(
            'newSizeMiB' => $newSizeMib
        );

        try {
            $this->makeRequest('PUT', $url, $model);
        } catch (TransferException $exception) {
            return false;
        }

        return true;
    }


    /**
     * Remove a volume from a volume set
     * @param $volumeSetName
     * @param $volumeName
     * @return bool|\stdClass
     * @throws ArtisanException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function removeVolumeFromVolumeSet($volumeSetName, $volumeName)
    {
        $baseUrl = $this->generateV1URL();
        $url = $baseUrl . '/volumeset/' . $volumeSetName . '/member/' . $volumeName;

        try {
            $this->makeRequest('DELETE', $url);
        } catch (TransferException $exception) {
            return false;
        }

        return true;
    }


    /**
     * Creates a host set
     * @param $hostSetIdentifier
     * @return bool|\StdClass
     * @throws ArtisanException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function createHostSet($hostSetIdentifier)
    {
        $baseUrl = $this->generateV1URL();
        $url = $baseUrl . '/hostset';

        $model = array(
            'hostSetIdentifier' => $hostSetIdentifier
        );

        try {
            $this->makeRequest('POST', $url, $model);
        } catch (TransferException $exception) {
            return false;
        }

        $HostSet = new \StdClass();
        $HostSet->name = $this->responseData->name;

        // Return the new host set name
        return $HostSet;
    }

    /**
     * Creates a volume set
     * @param $volumeSetIdentifier
     * @return bool|\StdClass
     * @throws ArtisanException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function createVolumeSet($volumeSetIdentifier)
    {
        $baseUrl = $this->generateV1URL();
        $url = $baseUrl . '/volumeset';

        $model = array(
            'volumeSetIdentifier' => $volumeSetIdentifier
        );

        try {
            $this->makeRequest('POST', $url, $model);
        } catch (TransferException $exception) {
            return false;
        }

        $volumeSet = new \StdClass();
        $volumeSet->name = $this->responseData->name;

        // Return the new volume set name
        return $volumeSet;
    }

    /**
     * Creates a volume
     * @param $volumeIdentifier
     * @param $type
     * @param $sizeMiB
     * @return bool|\StdClass
     * @throws ArtisanException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function createVolume($volumeIdentifier, $type, $sizeMiB)
    {
        $baseUrl = $this->generateV1URL();
        $url = $baseUrl . '/volume';

        $model = array(
            'volumeIdentifier' => $volumeIdentifier,
            'sizeMiB' => $sizeMiB,
            'type' => $type

        );

        try {
            $this->makeRequest('POST', $url, $model);
        } catch (TransferException $exception) {
            return false;
        }

        $volume = new \StdClass();
        $volume->name = $this->responseData->name;
        $volume->wwn = $this->responseData->wwn;
        $volume->sizeMiB = $this->responseData->sizeMiB;

        // Return the new volume set name
        return $volume;
    }


    /**
     * Export a volume set to a host set
     * @param $volumeSetName
     * @param $hostSetName
     * @return bool
     * @throws ArtisanException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function exportVolumeSet($volumeSetName, $hostSetName)
    {
        $baseUrl = $this->generateV1URL();
        $url = $baseUrl . '/volumeset/' . $volumeSetName . '/export';

        $model = array(
            'hostSetName' => $hostSetName
        );

        try {
            $this->makeRequest('POST', $url, $model);
        } catch (TransferException $exception) {
            return false;
        }

        return true;
    }

    /**
     * Unexport a volume set from a host set
     * @param $volumeSetName
     * @param $hostSetName
     * @return bool
     * @throws ArtisanException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function unExportVolumeSet($volumeSetName, $hostSetName)
    {
        $baseUrl = $this->generateV1URL();
        $url = $baseUrl . '/volumeset/' . $volumeSetName . '/export';

        $model = array(
            'hostSetName' => $hostSetName
        );

        try {
            $this->makeRequest('DELETE', $url, $model);
        } catch (TransferException $exception) {
            return false;
        }

        return true;
    }


    /**
     * Delete a host set
     * @param $hostSetName
     * @return bool
     * @throws ArtisanException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function deleteHostSet($hostSetName)
    {
        $baseUrl = $this->generateV1URL();
        $url = $baseUrl . '/hostset' . '/' . $hostSetName;

        try {
            $this->makeRequest('DELETE', $url);
        } catch (TransferException $exception) {
            return false;
        }

        return true;
    }

    /**
     * Delete a volume set
     * @param $volumeSetName
     * @return bool
     * @throws ArtisanException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function deleteVolumeSet($volumeSetName)
    {
        $baseUrl = $this->generateV1URL();
        $url = $baseUrl . '/volumeset' . '/' . $volumeSetName;

        try {
            $this->makeRequest('DELETE', $url);
        } catch (TransferException $exception) {
            return false;
        }

        return true;
    }


    /**
     * Creates a host
     * @param $hostIdentifier
     * @param array $FCWWNs
     * @param string $osType
     * @return bool|\stdClass
     * @throws ArtisanException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function createHost($hostIdentifier, array $FCWWNs = [], $osType = "VMWare")
    {
        $baseUrl = $this->generateV1URL();
        $url = $baseUrl . '/host';

        $model = array(
            'hostIdentifier' => $hostIdentifier,
            'FCWWNs' => $FCWWNs,
            'osType' => $osType
        );

        try {
            $this->makeRequest('POST', $url, $model);
        } catch (TransferException $exception) {
            return false;
        }

        $Host = new \StdClass();
        $Host->name = $this->responseData->name;

        // Return the new host name
        return $Host;
    }


    /**
     * Handles instances of TransferException
     * @param TransferException $exception
     * @return void
     */
    protected function handleException(TransferException $exception): void
    {
        $logMessage = 'Failed to make request to ' . $this->getRequestMethod() . ' ' . $this->getRequestUrl();
        $info = [];

        $this->lastError = $exception->getMessage();

        // Get the full exception message because Guzzle truncates it
        $this->response = $response = $exception->getResponse();

        if (is_null($response)) {
            $logMessage .= ' No response body from Artisan request, service may be unavailable.';
            Log::critical($logMessage);
            return;
        }

        $stream = $response->getBody();
        $stream->rewind();
        $responseBody = $stream->getContents();

        if (!empty($responseBody)) {
            $exceptionData = json_decode($responseBody, true);

            // If we can't JSON-decode it, there's not much we can do with the response
            if ((json_last_error() !== JSON_ERROR_NONE) || empty($exceptionData)) {
                $logMessage .= ' Invalid or missing response message.';
                Log::critical($logMessage);
                return;
            }

            // We don't care about logging the stack trace
            unset($exceptionData['StackTrace']);

            if (array_key_exists('ExceptionMessage', $exceptionData)) {
                $this->lastError = $exceptionData['ExceptionMessage'];
            }

            $info['response'] = $exceptionData;
        }

        Log::critical($logMessage, $info);
        return;
    }

    /**
     * Make a request
     * @param string $method
     * @param string $url
     * @param array|null $data
     * @return ResponseInterface
     * @throws ArtisanException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function makeRequest(string $method, string $url, array $data = array())
    {
        $this->requestData = $data;
        $this->requestMethod = $method;
        $this->requestUrl = $url;
        $this->response = null;
        $this->responseData = null;

        if (empty($this->sanUsername) || empty($this->sanPassword)) {
            throw new ArtisanException('Invalid credentials');
        }

        // Always set the authentication options
        $requestOptions = [
            'auth' => [$this->authUsername, $this->authPassword],
            'headers' => [
                "X-UKFast-SAN-Username" => $this->sanUsername,
                "X-UKFast-SAN-Password" => $this->sanPassword
            ]
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
            throw $exception;
        }

        return $this->response;
    }
}
