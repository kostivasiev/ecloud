<?php

namespace App\Rules\V2\Instance;

use App\Models\V2\Volume;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class IsNotSharedVolume implements Rule
{

    public function passes($attribute, $value)
    {
        return !Volume::forUser(Auth::user())->findOrFail($value)->is_shared;
    }

    public function message()
    {
        return 'Shared volumes cannot be attached directly to instances';
    }
}
