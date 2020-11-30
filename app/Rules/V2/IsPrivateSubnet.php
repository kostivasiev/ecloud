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
        $valid = false;
        try {
            $valid = $subnet->getRangeType() === Type::T_PRIVATENETWORK;
        } catch (\Exception $e) {
            // nothing to do
        }
        return $valid;
    }

    /**
     * @inheritDoc
     */
    public function message()
    {
        return 'The :attribute must be a private CIDR range';
    }
}
