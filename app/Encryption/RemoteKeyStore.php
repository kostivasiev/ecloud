<?php

namespace App\Encryption;

use GuzzleHttp\Client;

class RemoteKeyStore
{
    /**
     * HTTP client to make requests to the remote key store
     * @var string $httpClient
     */
    protected $httpClient;

    /**
     * Encryption key
     * @var string $key
     */
    protected $key;

    /**
     * Maximum length of the encryption key
     * @var int $maxKeyLength
     */
    public static $maxKeyLength = 32;

    /**
     * RemoteKeyStore constructor.
     * @param Client $httpClient
     */
    public function __construct($httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * Return HTTP client
     * @return Client
     */
    public function getHttpClient()
    {
        return $this->httpClient;
    }

    /**
     * Returns the encryption key
     * @param string $url
     * @return string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getKey(string $url): string
    {
        // phpcs:disable
        $this->key = getenv('DEV_AES_KEY');
        // phpcs:enable

        if (getenv('APP_ENV') !== 'local') {
            try {
                $response = $this->httpClient->request('GET', $url);
                $this->key = trim($response->getBody());
            } catch (\Exception $exception) {
                throw new \Exception('Failed to retrieve encryption key: ' . $exception->getMessage());
            }
        }

        // Ensure encryption key isn't too large
        if (strlen($this->key) > static::$maxKeyLength) {
            throw new \Exception('Encryption key length exceeds 32 characters');
        }

        return $this->key;
    }

    /**
     * Retrieves an encryption key
     * @return string
     */
    public static function getEncryptionKey()
    {
        $clientOptions = [
            'base_uri' => env('KEY_SERVER_HOST'),
            'timeout' => 2,
            'verify' => app()->environment() === 'production',
        ];
        $httpClient = new Client($clientOptions);
        $keyStore = new static($httpClient);

        return $keyStore->getKey('int/banner.html');
    }
}
