<?php

namespace App\Rules\V2\FloatingIp;

use App\Models\V2\FloatingIp;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class IsAssigned implements Rule
{

    public function passes($attribute, $value)
    {
        $floatingIp = FloatingIp::forUser(Auth::user())->findOrFail($value);

        return empty($floatingIp->floatingIpResource()->exists());
    }

    /**
     * @inheritDoc
     */
    public function message()
    {
        return 'The Floating IP is already assigned to a resource.';
    }
}
