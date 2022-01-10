<?php

namespace App\Rules\V2;

use App\Models\V2\RegionAble;
use App\Support\Resource;
use Illuminate\Contracts\Validation\Rule;

class IsSameRegion implements Rule
{
    public string $resourceId;

    public function __construct(string $resourceId)
    {
        $this->resourceId = $resourceId;
    }

    public function passes($attribute, $value)
    {
        if (empty($this->resourceId)) {
            return true;
        }

        $resourceClass = Resource::classFromId($this->resourceId);
        if (empty($resourceClass)) {
            return false;
        }

        $resource1 = $resourceClass::findOrFail($this->resourceId);

        $resource2Class = Resource::classFromId($value);
        if (empty($resource2Class)) {
            return false;
        }

        $resource2 = $resource2Class::findOrFail($value);

        if (!($resource2 instanceof RegionAble)) {
            return true;
        }

        if ($resource1 instanceof RegionAble) {
            return $resource1->region->id == $resource2->region->id;
        }

        if ($resource1 instanceof Region) {
            return $resource1->id == $resource2->region->id;
        }

        return true;
    }

    public function message()
    {
        return 'Resources must be in the same Region';
    }
}