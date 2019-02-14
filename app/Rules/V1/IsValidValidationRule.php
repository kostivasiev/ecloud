<?php

namespace App\Rules\V1;

use Illuminate\Contracts\Validation\Rule;

/**
 * Class IsValidValidationRule
 *
 * Determines whether an Appliance script parameter user provided validation rule is a valid RegEx
 *
 * @package App\Rules\V1
 */
class IsValidValidationRule implements Rule
{

    public function passes($attribute, $value)
    {
        // Check if the value is a valid regex
        return (@preg_match($value, null) !== false);
    }

    /**
     * @inheritDoc
     */
    public function message()
    {
        return 'The :attribute is not a valid regular expression';
    }
}
