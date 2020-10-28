<?php

namespace App\Rules\V2;

use Illuminate\Contracts\Validation\Rule;
use IPLib\Factory;

/**
 * Class ValidCidrRange
 * @package App\Rules\V2
 */
class ValidCidrRange implements Rule
{
    public function passes($attribute, $value)
    {
        if (!strpos($value, '-')) {
            return false;
        }

        list($from, $to) = explode('-', $value);
        return !is_null(Factory::rangeFromBoundaries($from, $to));
    }

    /**
     * @inheritDoc
     */
    public function message()
    {
        return 'The :attribute must be a valid CIDR range';
    }
}
