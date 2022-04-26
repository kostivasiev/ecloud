<?php

namespace App\Rules\V2\Instance;

use App\Models\V2\Instance;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class IsMaxNicLimit implements Rule
{
    public function passes($attribute, $value)
    {
        $instance = Instance::forUser(Auth::user())->findOrFail($value);

        if ($instance->nics->count() < config('instance.nics.max')) {
            return true;
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function message()
    {
        return 'The maximum number of NICs for the instance has been reached';
    }
}
