<?php

namespace App\Exceptions\V1;

use UKFast\Api\Exceptions\NotFoundException;

class PodNotFoundException extends NotFoundException
{
    public $title = 'Pod not found';
    public $message = 'A pod matching the requested ID was not found';
}
