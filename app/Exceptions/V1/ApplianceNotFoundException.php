<?php

namespace App\Exceptions\V1;

use UKFast\Api\Exceptions\NotFoundException;

class ApplianceNotFoundException extends NotFoundException
{
    public $title = 'Appliance not found';
    public $message = 'An appliance matching the requested ID was not found';
}
