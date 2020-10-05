<?php

namespace App\Exceptions\V1;

use UKFast\Api\Exceptions\NotFoundException;

class TemplateNotFoundException extends NotFoundException
{
    public $title = 'Template not found';
    public $message = 'A template matching the requested name was not found';
}
