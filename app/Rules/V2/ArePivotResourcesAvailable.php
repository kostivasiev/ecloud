<?php

namespace App\Rules\V2;

use App\Support\Sync;
use Illuminate\Contracts\Validation\Rule;

class ArePivotResourcesAvailable implements Rule
{
    protected Array $resources;

    protected String $pivot;

    protected String $failedResource;

    public function __construct($pivot, $resources)
    {
        $this->pivot = $pivot;

        $this->resources = $resources;
    }

    public function passes($attribute, $value)
    {
        $resource = $this->pivot::with($this->resources)->findOrFail($value);

        foreach ($resource->getRelations() as $relation) {
            if ($relation->sync->status != Sync::STATUS_COMPLETE) {
                $this->failedResource = $relation;
                return false;
            }
        }

        return true;
    }

    public function message()
    {
        return "The $this->failedResource is currently in a failed state and cannot be used";
    }
}
