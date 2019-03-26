<?php

namespace App\Solution\Exceptions;

use UKFast\Api\Exceptions\ApiException;

class InvalidSolutionStateException extends ApiException
{
    /**
     * {@inheritdoc}
     */
    public $message = 'VM is in an invalid state for that action';

    /**
     * {@inheritdoc}
     */
    public $statusCode = 403;

    /**
     * @var string
     */
    protected $state;

    public function __construct($state)
    {
        $this->state = $state;
        $this->title = 'Invalid Solution state';
    }

    /**
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }
}
