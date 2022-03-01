<?php

namespace App\Exceptions\V1;

use UKFast\Api\Exceptions\UnprocessableEntityException;

class ApplianceServerLicenseNotFoundException extends UnprocessableEntityException
{
    public $title = 'Appliance server license not found';
    public $message = 'No server license assigned to the Appliance';
}
