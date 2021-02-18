<?php

namespace App\Rules\V2;

use App\Models\V2\Instance;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class IsMaxVolumeLimitReached implements Rule
{
    private int $volumeMountLimit;

    public function __construct()
    {
        $this->volumeMountLimit = config('volume.instance.limit', 15);
    }

    public function passes($attribute, $value)
    {
        $instance = Instance::forUser(Auth::user())->findOrFail($value);
        return ($instance->volumes()->get()->count() < $this->volumeMountLimit);
    }

    public function message()
    {
        return 'The instance has reached the maximum mounted volume limit ('.$this->volumeMountLimit.')';
    }
}
