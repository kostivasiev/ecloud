<?php

namespace App\Exceptions\V1;

use UKFast\Api\Exceptions\ApiException;

class ServiceUnavailableException extends ApiException
{
    public $title = 'Service Unavailable';
    public $statusCode = 503;
}
