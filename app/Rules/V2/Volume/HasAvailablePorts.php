<?php

namespace App\Rules\V2\Volume;

use App\Models\V2\VolumeGroup;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class HasAvailablePorts implements Rule
{
    public function passes($attribute, $value)
    {
        $volumeGroup = VolumeGroup::forUser(Auth::user())->findOrFail($value);

        return $volumeGroup->volumes()->count() < config('volume-group.max_ports');
    }

    public function message()
    {
        return 'Maximum port count reached for the specified volume group';
    }
}
