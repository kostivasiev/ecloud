<?php
namespace App\Rules\V2;

use App\Models\V2\Instance;
use App\Models\V2\Volume;
use Illuminate\Contracts\Validation\Rule;

class VolumeNotAttached implements Rule
{
    protected Volume $volume;

    public function __construct(Volume $volume)
    {
        $this->volume = $volume;
    }

    public function passes($attribute, $value)
    {
        $instance = Instance::findOrFail($value);
        if ($instance->volumes()->count() == 0) {
            return true;
        }
        return ($instance->volumes()->where('volume_id', '=', $this->volume->id)->count() == 0);
    }

    public function message()
    {
        return 'The specified volume is already mounted on this instance';
    }
}