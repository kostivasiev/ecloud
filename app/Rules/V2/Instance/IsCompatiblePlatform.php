<?php

namespace App\Rules\V2\Instance;

use App\Models\V2\HostGroup;
use App\Models\V2\Instance;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class IsCompatiblePlatform implements Rule
{
    public function passes($attribute, $value)
    {
        $hostGroup = HostGroup::forUser(Auth::user())->findOrFail($value);
        $instance = Instance::forUser(Auth::user())->findOrFail(app('request')->route('instanceId'));

        if ($instance->image->platform == 'Windows' && !$hostGroup->windows_enabled) {
            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function message()
    {
        return 'The specified :attribute was not found';
    }
}