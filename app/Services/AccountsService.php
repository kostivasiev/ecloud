<?php

namespace App\Services;

use App\Exceptions\V1\ServiceResponseException;
use GuzzleHttp\Exception\TransferException;
use Liquid\Exception\NotFoundException;

/**
 * Class AccountsService
 * @package App\Services
 */
class AccountsService extends AbstractApioService
{
    /**
     * Service Name
     * @var string
     */
    protected $serviceName = 'accounts';

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
    public function assignVmEncryptionCredit(int $serverId): bool
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
    public function refundVmEncryptionCredit(int $serverId): bool
    {
        try {
            $response = $this->makeRequest('DELETE', 'v1/credits/ecloud_vm_encryption/' . $serverId);

            return ($response->getStatusCode() == 204);
        } catch (TransferException $exception) {
            return false;
        }
    }

    /**
     * Get Customers Payment Method
     * @param $resellerId
     * @return bool
     * @throws ServiceResponseException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getPaymentMethod($resellerId)
    {
        try {
            $response = $this->makeRequest('GET', 'v1/customers/' . $resellerId . '');
            if ($response->getStatusCode() !== 200) {
                throw new \Exception('unexpected response (' . $response->getStatusCode() . ') from accounts apio');
            }

            $data = $this->parseResponseData($response->getBody()->getContents())->data;
            if (empty($data->payment_method)) {
                throw new \Exception('missing account payment method');
            }

            return $data->payment_method;
        } catch (TransferException $exception) {
            throw new ServiceResponseException('unable to confirm account payment method');
        }
    }

    /**
     * @param $resellerId
     * @return bool
     * @throws ServiceResponseException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function isDemoCustomer($resellerId)
    {
        try {
            $response = $this->makeRequest('GET', 'v1/customers/' . $resellerId . '');
            if ($response->getStatusCode() !== 200) {
                throw new \Exception('unexpected response (' . $response->getStatusCode() . ') from accounts apio');
            }

            $data = $this->parseResponseData($response->getBody()->getContents())->data;
            if (!is_bool($data->is_demo_account)) {
                throw new \Exception('missing demo account status');
            }

            return $data->is_demo_account == true;
        } catch (Exception $exception) {
            throw new ServiceResponseException('unable to confirm account status');
        }
    }

    public function getPrimaryContactId($resellerId)
    {
        try {
            /** @var \GuzzleHttp\Psr7\Response $response */
            $response = $this->makeRequest('GET', 'v1/customers/' . $resellerId);
            if ($response->getStatusCode() !== 200) {
                throw new \Exception('unexpected response (' . $response->getStatusCode() . ') from accounts apio');
            }
            $data = $this->parseResponseData($response->getBody()->getContents())->data;
            return $data->primary_contact_id;
        } catch (\Exception $exception) {
            throw new ServiceResponseException('unable to retrieve account data');
        }
    }

    public function getPrimaryContactEmail($resellerId)
    {
        $primaryContactId = $this->getPrimaryContactId($resellerId);
        try {
            /** @var \GuzzleHttp\Psr7\Response $response */
            $response = $this->makeRequest('GET', 'v1/contacts/' . $primaryContactId);
            if ($response->getStatusCode() !== 200) {
                throw new \Exception('unexpected response (' . $response->getStatusCode() . ') from accounts apio');
            }
            $data = $this->parseResponseData($response->getBody()->getContents())->data;
            return $data->email_address;
        } catch (\Exception $exception) {
            throw new ServiceResponseException('unable to retrieve primary contact data');
        }
    }
}
