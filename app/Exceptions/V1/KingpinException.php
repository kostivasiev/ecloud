<?php

namespace App\Exceptions\V1;

use UKFast\Api\Exceptions\ApiException;

class KingpinException extends ApiException
{
    public $statusCode = 503;
    public $title = 'eCloud Service Exception';

    public function __construct($message = '', $source = '', $errorCode = 503, $previous = null)
    {
        parent::__construct($message, $source, $errorCode, $previous);
    }
}
