<?php

namespace App\Exceptions\V1;

use UKFast\Api\Exceptions\ApiException;

class TemplateUpdateException extends ApiException
{
    public $statusCode = 500;
    public $title = '';

    public function __construct($message = '', $source = '', $errorCode = 503, $previous = null)
    {
        parent::__construct($message, $source, $errorCode, $previous);
    }
}
