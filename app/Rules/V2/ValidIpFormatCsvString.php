<?php

namespace App\Rules\V2;

use Illuminate\Contracts\Validation\Rule;

class ValidIpFormatCsvString implements Rule
{
    /**
     * Validate the value is a comma separated list of IP address or range:
     * - IPv4 address (0.0.0.0)
     * - CIDR subnet range (0.0.0.0/24)
     * - Range boundaries (0.0.0.0-0.0.0.0)
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        if (empty($value)) {
            return false;
        }

        $valueArray = explode(',', $value);

        foreach ($valueArray as $valueItem) {
            if (!(new ValidRangeBoundaries())->passes($attribute, $valueItem) &&
                !(new ValidCidrSubnet())->passes($attribute, $valueItem) &&
                !(new ValidIpv4())->passes($attribute, $valueItem)
            ) {
                return false;
            }
        }
        return true;
    }

    public function message()
    {
        return 'The :attribute must contain a valid comma separated list of IPv4, CIDR subnets and/or range boundaries';
    }
}
