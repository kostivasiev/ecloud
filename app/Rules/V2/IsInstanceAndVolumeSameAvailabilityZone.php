<?php
namespace App\Rules\V2;

use App\Models\V2\Instance;
use App\Models\V2\Volume;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class IsInstanceAndVolumeSameAvailabilityZone implements Rule
{
    private $volumeId;

    public function __construct($volumeId)
    {
        $this->volumeId = $volumeId;
    }

    public function passes($attribute, $value)
    {
        $instance = Instance::forUser(Auth::user())->findOrFail($value);
        $volume = Volume::forUser(Auth::user())->findOrFail($this->volumeId);

        return $volume->availability_zone_id === $instance->availability_zone_id;
    }

    public function message()
    {
        return 'The instance is not in the same availability zone as the volume';
    }
}
