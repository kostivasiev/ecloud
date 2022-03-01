<?php
namespace App\Rules\V2;

use App\Models\V2\Instance;
use App\Models\V2\Volume;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Auth;

/**
 * @deprecated use instance volume
 */
class VolumeNotAttached implements Rule
{
    protected string $volumeId;

    public function __construct(string $volumeId)
    {
        $this->volumeId = $volumeId;
    }

    public function passes($attribute, $value)
    {
        $instance = Instance::forUser(Auth::user())->findOrFail($value);
        if ($instance->volumes()->count() == 0) {
            return true;
        }
        return ($instance->volumes()->where('volume_id', '=', $this->volumeId)->count() == 0);
    }

    public function message()
    {
        return 'The specified volume is already attached to this instance';
    }
}
