<?php

namespace App\Rules\V2;

use App\Support\Sync;
use Illuminate\Contracts\Validation\Rule;

class IsResourceAvailable implements Rule
{
    protected $resource;

    private bool $busy = false;

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

        switch ($instance->sync->status) {
            case Sync::STATUS_COMPLETE:
                return true;
            case Sync::STATUS_INPROGRESS:
                $this->busy = true;
                // no break
            case Sync::STATUS_FAILED:
                return false;
            default:
                throw new \Exception('Unexpected Resource State');
        }
    }

    public function message()
    {
        if ($this->busy != true) {
            return 'The specified :attribute resource is currently in a failed state and cannot be used';
        }

        return 'The specified :attribute resource is currently in progress and cannot be used';
    }
}
