<?php

namespace App\Exceptions\V1;

use UKFast\Api\Exceptions\NotFoundException;

class SolutionNotFoundException extends NotFoundException
{
    public $title = 'Solution not found';
    public $message = 'A solution matching the requested ID was not found';
}
