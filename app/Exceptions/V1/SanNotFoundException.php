<?php

namespace App\Exceptions\V1;

use UKFast\Api\Exceptions\NotFoundException;

class SanNotFoundException extends NotFoundException
{
    public $title = 'SAN not found';
    public $message = 'A SAN matching the requested ID was not found';
}
