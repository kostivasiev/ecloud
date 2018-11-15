<?php

namespace App\Exceptions\V1;

use UKFast\Api\Exceptions\ApiException;

class ServiceTimeoutException extends ApiException
{
    public $statusCode = 424;
    public $title = 'Service Timed Out';

    public function __construct($message = '', $source = '', $errorCode = 503, $previous = null)
    {
        parent::__construct($message, $source, $errorCode, $previous);
    }
}
