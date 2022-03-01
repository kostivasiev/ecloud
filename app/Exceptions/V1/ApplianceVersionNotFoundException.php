<?php

namespace App\Exceptions\V1;

use UKFast\Api\Exceptions\NotFoundException;

class ApplianceVersionNotFoundException extends NotFoundException
{
    public $title = 'Appliance version not found';
    public $message = 'A version of the appliance matching the requested ID was not found';
}
