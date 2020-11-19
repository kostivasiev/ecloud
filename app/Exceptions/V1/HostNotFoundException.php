<?php

namespace App\Exceptions\V1;

use UKFast\Api\Exceptions\NotFoundException;

class HostNotFoundException extends NotFoundException
{
    public $title = 'Host not found';
    public $message = 'A host matching the requested ID was not found';
}
