<?php

namespace App\Exceptions\V1;

use UKFast\Api\Exceptions\ApiException;

class ServiceResponseException extends ApiException
{
    public $title = 'Service Invalid Response';
    public $statusCode = 502;
}
