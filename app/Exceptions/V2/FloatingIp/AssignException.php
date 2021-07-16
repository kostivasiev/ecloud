<?php

namespace App\Exceptions\V2\FloatingIp;

use Symfony\Component\HttpFoundation\Response;
use UKFast\Api\Exceptions\ApiException;

class AssignException extends ApiException
{
    public $statusCode = Response::HTTP_CONFLICT;
    public $title = 'Assign Floating IP Failed';

    public function __construct()
    {
        parent::__construct("The Floating IP is already assigned to a resource.", "", $this->statusCode, null);
    }
}
