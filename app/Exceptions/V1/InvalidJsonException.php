<?php

namespace App\Exceptions\V1;

use UKFast\Api\Exceptions\ApiException;

class InvalidJsonException extends ApiException
{
    public $statusCode = 400;
    public $title = 'Invalid JSON';
}
