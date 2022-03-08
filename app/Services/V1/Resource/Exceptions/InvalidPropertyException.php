<?php
namespace App\Services\V1\Resource\Exceptions;

use UKFast\Api\Exceptions\ApiException;

class InvalidPropertyException extends ApiException
{
    public $statusCode = 500;
    public $title = 'Invalid Property Type';
}
