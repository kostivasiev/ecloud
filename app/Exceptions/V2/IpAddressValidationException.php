<?php

namespace App\Exceptions\V2;

use Symfony\Component\HttpFoundation\Response;
use UKFast\Api\Exceptions\ApiException;

class IpAddressValidationException extends ApiException
{
    public $statusCode = Response::HTTP_FAILED_DEPENDENCY;
    public $title = 'Validation Failure';
    public $detail = 'Failed to check ip address availability';

    public function __construct()
    {
        parent::__construct($this->detail, '', $this->statusCode, null);
    }
}
