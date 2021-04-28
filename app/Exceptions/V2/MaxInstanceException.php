<?php

namespace App\Exceptions\V2;

use Symfony\Component\HttpFoundation\Response;
use UKFast\Api\Exceptions\ApiException;

class MaxInstanceException extends ApiException
{
    public $statusCode = Response::HTTP_UNPROCESSABLE_ENTITY;
    public $title = 'Validation Error';

    public function __construct()
    {
        parent::__construct(
            'The maximum number of ' . config('instance.max_limit.total') . ' Instances per Customer have been reached',
            "",
            $this->statusCode,
            null
        );
    }
}
