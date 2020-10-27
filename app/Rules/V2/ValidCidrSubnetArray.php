<?php

namespace App\Rules\V2;

use Illuminate\Contracts\Validation\Rule;
use IPLib\Range\Subnet;

/**
 * Class ValidCidrSubnetArray
 * @package App\Rules\V2
 */
class ValidCidrSubnetArray extends ValidCidrSubnet implements Rule
{
    public function passes($attribute, $value)
    {
        if (!strpos($value, ",") === false) {
            $valueArray = explode(",", $value);
            foreach ($valueArray as $valueItem) {
                if (!parent::passes($attribute, $valueItem)) {
                    return false;
                }
            }
            return true;
        }
        return parent::passes($attribute, $value);
    }

    /**
     * @inheritDoc
     */
    public function message()
    {
        return 'The :attribute must contain a valid CIDR subnet';
    }
}
