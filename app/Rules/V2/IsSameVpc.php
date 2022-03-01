<?php
namespace App\Rules\V2;

use App\Models\V2\Vpc;
use App\Models\V2\VpcAble;
use App\Support\Resource;
use Illuminate\Contracts\Validation\Rule;

class IsSameVpc implements Rule
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

        if (!($resource2 instanceof VpcAble)) {
            return true;
        }

        if ($resource1 instanceof VpcAble) {
            return $resource1->vpc->id == $resource2->vpc->id;
        }

        if ($resource1 instanceof Vpc) {
            return $resource1->id == $resource2->vpc->id;
        }

        return true;
    }

    public function message()
    {
        return 'Resources must be in the same Vpc';
    }
}
