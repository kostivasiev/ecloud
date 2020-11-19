<?php

namespace App\Exceptions\V1;

use UKFast\Api\Exceptions\ApiException;

class ApplianceActiveException extends ApiException
{
    /**
     * HTTP response code
     * @var int
     */
    public $statusCode = 400;

    /**
     * Error message to display
     * @var string
     */
    public $title = 'Appliance Active';

    /**
     * InsufficientCreditsException  constructor.
     */
    public function __construct()
    {
        $message = 'The last active version of an appliance can not be deleted whilst the appliance is available in a Pod';
        parent::__construct($message);
    }
}
