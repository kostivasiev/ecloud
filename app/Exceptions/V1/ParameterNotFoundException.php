<?php

namespace App\Exceptions\V1;

use UKFast\Api\Exceptions\NotFoundException;

class ParameterNotFoundException extends NotFoundException
{
    public $title = 'Appliance parameter not found';
    public $message = 'An appliance parameter matching the requested ID was not found';
}
