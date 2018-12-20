<?php

namespace App\Exceptions\V1;

use UKFast\Api\Exceptions\ForbiddenException;

class InsufficientResourceException extends ForbiddenException
{
    public $statusCode = 403;
    public $title = 'Insufficient resources available';
}
