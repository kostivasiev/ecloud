<?php

namespace App\Rules\V2;

use Illuminate\Contracts\Validation\Rule;
use IPLib\Range\Subnet;
use IPLib\Range\Type;

/**
 * Class IsPrivateSubnet
 * @package App\Rules\V2
 */
class IsPrivateSubnet implements Rule
{
    public function passes($attribute, $value)
    {
        $subnet = Subnet::fromString($value);
        return ($subnet) ? $subnet->getRangeType() === Type::T_PRIVATENETWORK : false;
    }

    /**
     * @inheritDoc
     */
    public function message()
    {
        return 'The :attribute must be a private CIDR range';
    }
}
