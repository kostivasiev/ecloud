<?php

namespace App\Services;

use GuzzleHttp\Exception\TransferException;

/**
 * Class AccountsService
 * @package App\Services
 */
class AccountsService extends AbstractApioService
{
    /**
     * Get VM encryption credit info
     * @return bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getVmEncryptionCredits()
    {
        try {
            $response = $this->makeRequest('GET', 'v1/credits/ecloud_vm_encryption');

            if ($response->getStatusCode() !== 200) {
                return false;
            }

            return $this->parseResponseData($response->getBody()->getContents())->data;
        } catch (TransferException $exception) {
            return false;
        }
    }

    /**
     * Assign VM encryption credit
     * @param Int $serverId
     * @return bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function assignVmEncryptionCredit(Int $serverId): bool
    {
        try {
            $data = ['resource_id' => $serverId];

            $response = $this->makeRequest('POST', 'v1/credits/ecloud_vm_encryption', $data);

            return ($response->getStatusCode() == 204);
        } catch (TransferException $exception) {
            return false;
        }
    }

    /**
     * Refund VM encryption credit
     * @param Int $serverId
     * @return bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function refundVmEncryptionCredit(Int $serverId): bool
    {
        try {
            $response = $this->makeRequest('DELETE', 'v1/credits/ecloud_vm_encryption/' . $serverId);

            return ($response->getStatusCode() == 204);
        } catch (TransferException $exception) {
            return false;
        }
    }
}
