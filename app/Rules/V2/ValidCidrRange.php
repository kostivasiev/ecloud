<?php

namespace App\Rules\V2;

use Illuminate\Contracts\Validation\Rule;
use IPLib\Factory as CidrRange;

/**
 * Class ValidCidrRange
 * @package App\Rules\V2
 */
class ValidCidrRange implements Rule
{
    public function passes($attribute, $value)
    {
        $range = null;
        if (strpos($value, '-')) {
            list($firstPart, $secondPart) = explode("-", $value);
            $range = CidrRange::rangeFromBoundaries($firstPart, $secondPart);
        }
        return !is_null($range);
    }

    /**
     * @inheritDoc
     */
    public function message()
    {
        return 'The :attribute must be a valid CIDR range';
    }
}
