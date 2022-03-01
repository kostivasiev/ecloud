<?php

namespace App\Exceptions\V1;

use UKFast\Api\Exceptions\ApiException;

class InsufficientCreditsException extends ApiException
{
    /**
     * HTTP response code
     * @var int
     */
    public $statusCode = 402;

    /**
     * Error message to display
     * @var string
     */
    public $title = 'Insufficient Credits';

    /**
     * InsufficientCreditsException  constructor.
     */
    public function __construct()
    {
        $message = 'You have insufficient credits to purchase this product. Please contact your account manager';
        parent::__construct($message);
    }
}
