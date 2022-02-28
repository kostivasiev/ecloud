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
            return sprintf('The specified :attribute resource currently has the status of \'%s\' and cannot be used', Sync::STATUS_FAILED);
        }

        return sprintf('The specified :attribute resource currently has the status of \'%s\' and cannot be used', Sync::STATUS_INPROGRESS);
    }
}
