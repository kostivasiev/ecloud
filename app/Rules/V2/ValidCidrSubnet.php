<?php

namespace App\Rules\V2;

use Illuminate\Contracts\Validation\Rule;
use IPLib\Range\Subnet;

/**
 * Class ValidCidrSubnetRange
 * Validates that the value is a CIDR subnet range with /mask e.g. 10.0.0.0/24
 * Validates that the smallest subnet mask allowed is /29. A mask number > 29
 * would not leave enough usable IP's
 * @package App\Rules\V2
 */
class ValidCidrSubnet implements Rule
{
    public function passes($attribute, $value)
    {
        $subnet = Subnet::fromString($value);
        return !is_null($subnet);
    }

    /**
     * @inheritDoc
     */
    public function message()
    {
        return 'The :attribute must be a valid CIDR subnet';
    }
}
