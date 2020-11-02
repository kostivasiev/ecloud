<?php

namespace App\Rules\V2;

use Illuminate\Contracts\Validation\Rule;

class ValidRangeBoundariesOrCidrSubnetArray implements Rule
{
    public function passes($attribute, $value)
    {
        $valueArray = explode(',', $value);
        foreach ($valueArray as $valueItem) {
            if (!(new ValidRangeBoundaries())->passes($attribute, $valueItem) &&
                !(new ValidCidrSubnet())->passes($attribute, $valueItem)) {
                return false;
            }
        }
        return true;
    }

    public function message()
    {
        return 'The :attribute must contain a valid array of CIDR subnets and/or range boundaries';
    }
}
