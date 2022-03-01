<?php

namespace App\Datastore\Exceptions;

use App\Exceptions\V1\InsufficientResourceException;

class DatastoreInsufficientSpaceException extends InsufficientResourceException
{
    public $statusCode = 403;
    public $title = 'Datastore has insufficient space';
}
