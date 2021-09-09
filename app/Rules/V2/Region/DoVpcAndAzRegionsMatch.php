<?php
namespace App\Rules\V2\Region;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Vpc;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;

class DoVpcAndAzRegionsMatch implements Rule
{
    public string $attribute;
    public Model $resource;

    public function __construct($attribute)
    {
        $resourceId = Request::input($attribute);
        $this->resource = $this->getResource($attribute, $resourceId);
        $this->attribute = Str::of($attribute)->snake()->replace('_', ' ')->lower();
    }

    public function passes($attribute, $value)
    {
        $resource = $this->getResource($attribute, $value);
        if (empty($this->resource->region_id) || empty($resource->region_id)) {
            return true;
        }
        return $this->resource->region_id == $resource->region_id;
    }

    public function message()
    {
        return 'The :attribute and ' . $this->attribute . ' resources are not in the same region.';
    }

    public function getResource(string $attribute, string $resourceId)
    {
        if ($attribute == 'availability_zone_id') {
            return AvailabilityZone::forUser(Auth::user())->findOrFail($resourceId);
        }
        return Vpc::forUser(Auth::user())->findOrFail($resourceId);
    }
}
