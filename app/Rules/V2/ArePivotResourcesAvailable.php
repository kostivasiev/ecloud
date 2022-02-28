<?php

namespace App\Rules\V2;

use App\Support\Sync;
use Illuminate\Contracts\Validation\Rule;

class ArePivotResourcesAvailable implements Rule
{
    protected Array $resources;

    protected String $pivot;

    protected String $failedResource;

    private bool $inProgress;

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
                $this->inProgress = $relation->sync->status == Sync::STATUS_INPROGRESS;
                $this->failedResource = $relation;
                return false;
            }
        }

        return true;
    }

    public function message()
    {
        if ($this->inProgress != true) {
            return sprintf('The %s currently has the status of \'%s\' and cannot be used', $this->failedResource, Sync::STATUS_FAILED);
        }

        return sprintf('The %s currently has the status of \'%s\' and cannot be used', $this->failedResource, Sync::STATUS_INPROGRESS);
    }
}
