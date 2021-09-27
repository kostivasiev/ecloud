<?php

namespace App\Rules\V2\Volume;

use App\Models\V2\VolumeGroup;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class HasAvailableInstances implements Rule
{
    public function passes($attribute, $value)
    {
        $volumeGroup = VolumeGroup::forUser(Auth::user())->findOrFail($value);
        return $volumeGroup->instances()->count() < config('volume-group.max_instances');
    }

    public function message()
    {
        return 'Maximum instance count reached for the specified volume group';
    }
}
