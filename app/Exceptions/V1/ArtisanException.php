<?php

namespace App\Exceptions\V1;

use UKFast\Api\Exceptions\ApiException;

class ArtisanException extends ApiException
{
    public $statusCode = 503;
    public $title = 'Storage Network Exception';

    public function __construct($message = '', $source = '', $errorCode = 503, $previous = null)
    {
        parent::__construct($message, $source, $errorCode, $previous);
    }
}
