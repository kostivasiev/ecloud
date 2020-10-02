<?php

namespace App\Rules\V2;

use Illuminate\Contracts\Validation\Rule;

/**
 * Class ValidMacAddress
 * @package App\Rules\V2
 */
class ValidMacAddress implements Rule
{

    public function passes($attribute, $value)
    {
        return (bool)preg_match(
            "/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/",
            $value
        );
    }

    /**
     * @inheritDoc
     */
    public function message()
    {
        return 'The :attribute must be a valid MAC address';
    }
}
