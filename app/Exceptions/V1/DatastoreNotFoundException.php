<?php

namespace App\Exceptions\V1;

use UKFast\Api\Exceptions\NotFoundException;

class DatastoreNotFoundException extends NotFoundException
{
    public $title   = 'Datastore not found';
    public $message = 'A datastore matching the requested ID was not found';
}
