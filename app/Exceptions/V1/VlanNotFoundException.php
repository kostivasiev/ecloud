<?php

namespace App\Exceptions\V1;

use UKFast\Api\Exceptions\NotFoundException;

class VlanNotFoundException extends NotFoundException
{
    public $title   = 'vlan not found';
    public $message = 'A vlan matching the requested ID was not found';
}
