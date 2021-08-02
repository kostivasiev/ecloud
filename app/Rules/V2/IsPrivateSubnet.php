<?php

namespace App\Rules\V2;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Auth;
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
        if (!Auth::user()->isAdmin() && $value == '192.168.0.0/16') {
            return false;
        }
        $subnet = Subnet::fromString($value);
        return ($subnet) && $subnet->getRangeType() === Type::T_PRIVATENETWORK;
    }

    /**
     * @inheritDoc
     */
    public function message()
    {
        return 'The :attribute must be a valid private CIDR range';
    }
}
