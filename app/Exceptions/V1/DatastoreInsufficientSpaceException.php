<?php

namespace App\Exceptions\V1;

class DatastoreInsufficientSpaceException extends InsufficientResourceException
{
    public $statusCode = 403;
    public $title = 'Datastore has insufficient space';
}
