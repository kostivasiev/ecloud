<?php

namespace App\Exceptions;

use Symfony\Component\HttpFoundation\Response;
use UKFast\Api\Exceptions\ApiException;

class SyncException extends ApiException
{
    public $statusCode = Response::HTTP_CONFLICT;
    public $title = 'Resource unavailable';

    public function __construct()
    {
        parent::__construct("The specified resource is being modified and is unavailable at this time", "", $this->statusCode, null);
    }
}
