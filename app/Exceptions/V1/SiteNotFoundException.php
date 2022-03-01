<?php

namespace App\Exceptions\V1;

use UKFast\Api\Exceptions\NotFoundException;

class SiteNotFoundException extends NotFoundException
{
    public $title = 'Site not found';
    public $message = 'A site matching the requested ID was not found';
}
