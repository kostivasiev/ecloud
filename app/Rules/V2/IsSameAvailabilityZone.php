<?php
namespace App\Rules\V2;

use App\Models\V2\AvailabilityZoneable;
use App\Models\V2\Instance;
use App\Models\V2\Volume;
use App\Support\Resource;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class IsSameAvailabilityZone implements Rule
{
    private $resource;

    public function __construct(AvailabilityZoneable $resource)
    {
        $this->resource = $resource;
    }

    public function passes($attribute, $value)
    {
        $resource = Resource::classFromId($value)::findOrFail($value);

        if (!($resource instanceof AvailabilityZoneable)) {
            return false;
        }

        return $this->resource->availabilityZone->id == $resource->availabilityZone->id;
    }

    public function message()
    {
        return 'Resources must be in the same availability zone';
    }
}
