<?php

namespace App\Rules\V2;

use Illuminate\Contracts\Validation\Rule;
use IPLib\Range\Subnet;

/**
 * Class ValidCidrSubnetRange
 * Validates that the value is a CIDR subnet range with /mask e.g.
 * 10.0.0.0/24
 * @package App\Rules\V2
 */
class ValidCidrSubnetRange implements Rule
{
    public function passes($attribute, $value)
    {
        return !is_null(Subnet::fromString($value));
    }

    /**
     * @inheritDoc
     */
    public function message()
    {
        return 'The :attribute must be a valid CIDR subnet range';
    }
}
