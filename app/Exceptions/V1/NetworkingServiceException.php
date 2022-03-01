<?php

namespace App\Exceptions\V1;

use UKFast\Api\Exceptions\ApiException;

class NetworkingServiceException extends ApiException
{
    public $title = 'Network Service Unavailable';
    public $statusCode = 502;
}
