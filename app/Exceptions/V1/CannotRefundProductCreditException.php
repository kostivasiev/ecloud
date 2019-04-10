<?php
namespace App\Exceptions\V1;

use UKFast\Api\Exceptions\ApiException;

class CannotRefundProductCreditException extends ApiException
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
    public $title = 'Cannot Refund Product Credit';

    /**
     * CannotRefundProductCreditException constructor.
     */
    public function __construct()
    {
        $message = 'We are unable to refund your credit associated with this product';
        parent::__construct($message);
    }
}
