<?php

namespace App\Exceptions\V1;

use UKFast\Api\Exceptions\ApiException;

class DatastoreInsufficientSpaceException extends ApiException
{
    public $statusCode = 500;
    public $title = 'Datastore has insufficient space';
}
