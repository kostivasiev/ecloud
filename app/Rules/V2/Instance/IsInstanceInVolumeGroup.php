<?php

namespace App\Rules\V2\Instance;

use App\Models\V2\Instance;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class IsInstanceInVolumeGroup implements Rule
{
    public Instance $instance;

    public function __construct($instanceId)
    {
        $this->instance = Instance::forUser(Auth::user())->findOrFail($instanceId);
    }

    public function passes($attribute, $value)
    {
        return empty($this->instance->volume_group_id);
    }

    public function message()
    {
        return 'The instance is already a member of a volume group';
    }
}
