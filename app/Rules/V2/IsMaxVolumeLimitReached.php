<?php

namespace App\Rules\V2;

use App\Models\V2\Instance;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Auth;

/**
 * @deprecated use instance volume
 */
class IsMaxVolumeLimitReached implements Rule
{
    private int $volumeAttachLimit;

    public function __construct()
    {
        $this->volumeAttachLimit = config('volume.instance.limit', 15);
    }

    public function passes($attribute, $value)
    {
        $instance = Instance::forUser(Auth::user())->findOrFail($value);
        return ($instance->volumes()->get()->count() < $this->volumeAttachLimit);
    }

    public function message()
    {
        return 'The instance has reached the maximum attached volume limit (' . $this->volumeAttachLimit . ')';
    }
}
