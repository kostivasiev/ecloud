<?php

namespace App\Services;

class AccountsService extends AbstractApioService
{
    /**
     * API version
     * @var string
     */
    protected $api_version = "v1";

    /**
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function customers()
    {
        $response = $this->makeRequest('GET', 'credits');

        if ($response->getStatusCode() !== 200) {
            // throw exception

        }

        return $this->parseResponseData($response->getBody()->getContents());
    }
}
