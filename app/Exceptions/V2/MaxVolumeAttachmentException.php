<?php

namespace App\Exceptions\V2;

use Symfony\Component\HttpFoundation\Response;
use UKFast\Api\Exceptions\ApiException;

class MaxVolumeAttachmentException extends ApiException
{
    public $statusCode = Response::HTTP_UNPROCESSABLE_ENTITY;
    public $title = 'Validation Error';

    public function __construct()
    {
        parent::__construct(
            'The maximum number of ' . config('volume.instance.limit') . ' volume attachments has been reached',
            "",
            $this->statusCode,
            null
        );
    }
}
