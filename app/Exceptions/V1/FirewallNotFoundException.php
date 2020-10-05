<?php

namespace App\Exceptions\V1;

use UKFast\Api\Exceptions\NotFoundException;

class FirewallNotFoundException extends NotFoundException
{
    public $title = 'Firewall not found';
    public $message = 'A firewall matching the requested ID was not found';
}
