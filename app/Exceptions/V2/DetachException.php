<?php

namespace App\Exceptions\V2;

use Symfony\Component\HttpFoundation\Response;
use UKFast\Api\Exceptions\ApiException;

class DetachException extends ApiException
{
    public $statusCode = Response::HTTP_PRECONDITION_FAILED;
    public $title = 'Detach Failed';

    public function __construct()
    {
        parent::__construct("The specified volume cannot be detached from this instance.", "", $this->statusCode, null);
    }
}
