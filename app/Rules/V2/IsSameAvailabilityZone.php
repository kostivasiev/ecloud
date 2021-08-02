<?php
namespace App\Rules\V2;

use App\Models\V2\AvailabilityZoneable;
use App\Support\Resource;
use Illuminate\Contracts\Validation\Rule;

/**
 * If the two passed in resource types implement the AvailabilityZoneable interface
 * check that the two resources reside in the same availability zone.
 *
 * Class IsSameAvailabilityZone
 * @package App\Rules\V2
 */
class IsSameAvailabilityZone implements Rule
{
    private $resource1;

    public function __construct($resourceId)
    {
         if (!empty($resourceId)) {
             $this->resource1 = Resource::classFromId($resourceId)::findOrFail($resourceId);
         }
    }

    public function passes($attribute, $value)
    {
        if (!($this->resource1 instanceof AvailabilityZoneable)) {
            return true;
        }

        $resource2 = Resource::classFromId($value)::findOrFail($value);

        if (!($resource2 instanceof AvailabilityZoneable)) {
            return true;
        }

        return $this->resource1->availabilityZone->id == $resource2->availabilityZone->id;
    }

    public function message()
    {
        return 'Resources must be in the same availability zone';
    }
}
