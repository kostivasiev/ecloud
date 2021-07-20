<?php
namespace App\Rules\V2;

use App\Models\V2\Instance;
use App\Models\V2\Volume;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class IsVolumeAndInstanceSameAvailabilityZone implements Rule
{
    private $instanceId;

    public function __construct($instanceId)
    {
        $this->instanceId = $instanceId;
    }

    public function passes($attribute, $value)
    {
        $volume = Volume::forUser(Auth::user())->findOrFail($value);
        $instance = Instance::forUser(Auth::user())->findOrFail($this->instanceId);

        return $volume->availability_zone_id === $instance->availability_zone_id;
    }

    public function message()
    {
        return 'The volume is not in the same availability zone as the instance';
    }

}