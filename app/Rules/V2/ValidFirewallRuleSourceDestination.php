<?php

namespace App\Rules\V2;

use Illuminate\Contracts\Validation\Rule;

class ValidFirewallRuleSourceDestination implements Rule
{
    /**
     * Validate the value is 'ANY' or a comma separated list of IP address or range:
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
        if ($value === 'ANY') {
            return true;
        }
        $value = preg_replace('/\s+/', '', $value);
        return (new ValidIpFormatCsvString())->passes($attribute, $value);
    }

    public function message()
    {
        return "The :attribute must be 'ANY' or contain a valid comma separated list of IPv4, CIDR subnets and/or range boundaries";
    }
}
