<?php
namespace App\Services\V1\Resource\Exceptions;

use UKFast\Api\Exceptions\ApiException;

class InvalidRouteException extends ApiException
{
    public $statusCode = 500;
    public $title = 'Invalid Route';
}
