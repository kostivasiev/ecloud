<?php

namespace App\Exceptions\V1;

use UKFast\Api\Exceptions\ApiException;

class ServiceTimeoutException extends ApiException
{
    public $title = 'Service Timed Out';
    public $statusCode = 504;
}
