<?php

namespace App\VM\Exceptions;

use UKFast\Api\Exceptions\ApiException;

class InvalidVmStateException extends ApiException
{
    /**
     * {@inheritdoc}
     */
    public $message = 'VM is in an invalid state for that action';

    /**
     * {@inheritdoc}
     */
    public $statusCode = 400;

    /**
     * @var string
     */
    protected $state;

    public function __construct($state)
    {
        $this->state = $state;
        $this->title = 'Invalid VM state: ' . $state;
    }

    /**
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }
}
