<?php

namespace App\Rules\V2;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Auth;

/**
 * Class IsSubnetBigEnough
 * @package App\Rules\V2
 */
class IsScoped implements Rule
{
    public function passes($attribute, $value)
    {
        return Auth::user()->isScoped();
    }

    /**
     * @inheritDoc
     */
    public function message()
    {
        return 'The user does not have access to one of the resources.';
    }
}
