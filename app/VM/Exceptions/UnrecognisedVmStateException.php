<?php

namespace App\VM\Exceptions;

use UKFast\Api\Exceptions\ApiException;

class UnrecognisedVmStateException extends ApiException
{
    /**
     * {@inheritdoc}
     */
    public $message = 'Failed to put VM into unrecognised state';

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
        $this->title = 'Unrecognised VM state: ' . $state;
    }

    /**
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }
}
