<?php

namespace App\Exceptions\V1;

use UKFast\Api\Exceptions\ApiException;

class IntapiServiceException extends ApiException
{
    public $title = 'INTAPI Service Unavailable';
    public $statusCode = 502;
}
