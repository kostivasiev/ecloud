<?php

namespace App\Exceptions\V1;

use UKFast\Api\Exceptions\NotFoundException;

class NetworkNotFoundException extends NotFoundException
{
    public $title = 'network not found';
    public $message = 'A network matching the requested ID was not found';
}
