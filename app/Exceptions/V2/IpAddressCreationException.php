<?php

namespace App\Exceptions\V2;

use Symfony\Component\HttpFoundation\Response;
use UKFast\Api\Exceptions\ApiException;

class IpAddressCreationException extends ApiException
{
    public $statusCode = Response::HTTP_FAILED_DEPENDENCY;
    public $title = 'Failed';
    public $detail = 'Failed to assign ip address';

    public function __construct()
    {
        parent::__construct($this->detail, '', $this->statusCode, null);
    }
}
