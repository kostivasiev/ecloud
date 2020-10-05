<?php

namespace App\Exceptions\V1;

use UKFast\Api\Exceptions\NotFoundException;

class TagNotFoundException extends NotFoundException
{
    public $title = 'Tag not found';
    public $message = 'A tag matching the requested key was not found';
}
