<?php
namespace App\Rules\V2;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\AvailabilityZoneable;
use App\Support\Resource;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Auth;

/**
 * If the two passed in resource types implement the AvailabilityZoneable interface
 * check that the two resources reside in the same availability zone.
 *
 * Class IsSameAvailabilityZone
 * @package App\Rules\V2
 */
class IsSameAvailabilityZone implements Rule
{
    private $resourceId;

    public function __construct($resourceId)
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

        if ($resource1 instanceof AvailabilityZone) {
            $resource1AvailabilityZoneID = $resource1->id;
        } else {
            if (!($resource1 instanceof AvailabilityZoneable)) {
                return true;
            }

            $resource1AvailabilityZoneID = $resource1->availabilityZone->id;
        }

        $resource2Class = Resource::classFromId($value);
        if (empty($resource2Class)) {
            return false;
        }

        $resource2 = $resource2Class::findOrFail($value);

        if (!($resource2 instanceof AvailabilityZoneable)) {
            return true;
        }

        return $resource1AvailabilityZoneID == $resource2->availabilityZone->id;
    }

    public function message()
    {
        return 'Resources must be in the same availability zone';
    }
}
