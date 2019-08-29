<?php

namespace App\Exceptions\V1;

use UKFast\Api\Exceptions\ApiException;

class ConflictException extends ApiException
{
    public $statusCode = 409;
    public $title = 'Conflict';

    public function __construct($message = '', $source = '', $errorCode = 0, $previous = null)
    {
        parent::__construct($message, $source, $errorCode, $previous);
    }
}
