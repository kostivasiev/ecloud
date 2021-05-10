<?php

namespace App\Exceptions\V2;

use Symfony\Component\HttpFoundation\Response;
use UKFast\Api\Exceptions\ApiException;

class TaskException extends ApiException
{
    public $statusCode = Response::HTTP_CONFLICT;
    public $title = 'Resource unavailable';

    public function __construct()
    {
        parent::__construct("The specified resource has a task in progress", "", $this->statusCode, null);
    }
}
