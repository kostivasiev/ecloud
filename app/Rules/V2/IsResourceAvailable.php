<?php

namespace App\Rules\V2;

use App\Support\Sync;
use Illuminate\Contracts\Validation\Rule;

class IsResourceAvailable implements Rule
{
    protected $resource;

    private bool $inProgress = false;

    public function __construct($resource)
    {
        $this->resource = new $resource;
    }

    public function passes($attribute, $value)
    {
        $instance = $this->resource->find($value);
        if (!$instance) {
            return false;
        }

        $this->inProgress = $instance->sync->status == Sync::STATUS_INPROGRESS;

        return $instance->sync->status === Sync::STATUS_COMPLETE;
    }

    public function message()
    {
        if ($this->inProgress != true) {
            return 'The specified :attribute resource is currently in a failed state and cannot be used';
        }

        return sprintf('The specified :attribute resource is currently %s and cannot be used', Sync::STATUS_INPROGRESS);
    }
}
