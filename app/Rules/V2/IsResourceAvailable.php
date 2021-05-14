<?php

namespace App\Rules\V2;

use App\Models\V2\Task;
use Illuminate\Contracts\Validation\Rule;

class IsResourceAvailable implements Rule
{
    protected $resource;

    public function __construct($resource)
    {
        $this->resource = new $resource;
    }

    public function passes($attribute, $value)
    {
        return ($this->resource->findOrFail($value))->sync->status !== Task::STATUS_FAILED;
    }

    public function message()
    {
        return 'The specified :attribute resource is currently in a failed state and cannot be used';
    }
}
