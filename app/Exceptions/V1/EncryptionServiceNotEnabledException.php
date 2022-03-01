<?php

namespace App\Exceptions\V1;

use UKFast\Api\Exceptions\ApiException;

class EncryptionServiceNotEnabledException extends ApiException
{
    public $statusCode = 403;
    public $title = 'Encryption Service Not Enabled';

    public function __construct($message = '', $source = '', $errorCode = 503, $previous = null)
    {
        parent::__construct($message, $source, $errorCode, $previous);
    }
}
