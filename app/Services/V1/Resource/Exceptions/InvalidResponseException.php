<?php
namespace App\Services\V1\Resource\Exceptions;

use UKFast\Api\Exceptions\ApiException;

class InvalidResponseException extends ApiException
{
    public $statusCode = 500;
    public $title = 'Invalid Response';
}
