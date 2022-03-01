<?php

namespace App\Solution;

use App\Models\V1\Solution;
use App\Solution\Exceptions\InvalidSolutionStateException;
use Illuminate\Support\Facades\Auth;

class CanModifyResource
{
    private $solution;

    /**
     * List of allowed statuses for a Solution
     *
     * @var array
     */
    const ALLOWED_STATUSES = [
        Status::COMPLETED
    ];


    public function __construct(Solution $solution)
    {
        $this->solution = $solution;
    }

    public function validate()
    {
        if (Auth::user()->isAdmin()) {
            return true;
        }

        if (collect(static::ALLOWED_STATUSES)->contains($this->solution->ucs_reseller_status)) {
            return true;
        }

        $exception = new InvalidSolutionStateException($this->solution->ucs_reseller_status);
        $exception->detail =
            'Cannot modify resources whilst solution state is: ' . $this->solution->ucs_reseller_status;

        throw $exception;
    }
}
