<?php

namespace App\Services;

use App\Exceptions\V1\ServiceResponseException;
use UKFast\Api\Exceptions;

/**
 * Class BillingService
 * @package App\Services
 */
class BillingService extends AbstractApioService
{
    /**
     * Service Name
     * @var string
     */
    protected $serviceName = 'billing';

    /**
     * Verify the customer has a valid default card on their account
     * @return bool
     * @throws Exceptions\PaymentRequiredException
     * @throws ServiceResponseException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function verifyDefaultPaymentCard()
    {
        try {
            $response = $this->makeRequest('GET', 'v1/cards?primary_card:eq=true');
            if ($response->getStatusCode() !== 200) {
                throw new \Exception('unexpected response (' . $response->getStatusCode() . ') from accounts apio');
            }

            $defaultCard = $this->parseResponseData($response->getBody()->getContents())->data[0];
            if (empty($defaultCard)) {
                throw new Exceptions\ApiException('no default payment card on account');
            }

            $expiryDate = \DateTime::createFromFormat(
                'm/y',
                $defaultCard->expiry
            )->modify('+1 month first day of midnight');

            if ($expiryDate < new \DateTime('now')) {
                throw new Exceptions\ApiException('default payment card has expired');
            }

            return true;
        } catch (Exceptions\ApiException $exception) {
            throw new Exceptions\PaymentRequiredException($exception->getMessage());
        } catch (\Exception $exception) {
            throw new ServiceResponseException('unable to confirm default payment details');
        }
    }
}
