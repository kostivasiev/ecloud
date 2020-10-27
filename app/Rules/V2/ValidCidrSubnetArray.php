<?php

namespace App\Rules\V2;

use Illuminate\Contracts\Validation\Rule;
use IPLib\Factory;
use IPLib\Range\Subnet;

/**
 * Class ValidCidrSubnetArray
 * @package App\Rules\V2
 */
class ValidCidrSubnetArray extends ValidCidrSubnet implements Rule
{
    public function passes($attribute, $value)
    {
        $valueArray = explode(",", $value);
        foreach ($valueArray as $valueItem) {
            $rangeValid = (new ValidCidrRange())->passes($attribute, $valueItem);
            if (!$rangeValid && !parent::passes($attribute, $valueItem)) {
                return false;
            }
        }
        return true;
    }

    /**
     * @inheritDoc
     */
    public function message()
    {
        return 'The :attribute must contain a valid CIDR subnet or subnet range';
    }
}
