<?php

namespace App\Rules\V2;

use Illuminate\Contracts\Validation\Rule;

class ValidFirewallRulePortSourceDestination implements Rule
{
    /**
     * Validate the value is 'ANY' or a port / port range:
     * - ANY
     * - Port (80)
     * - Port range (80-90)
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

        return (new ValidPortReference())->passes($attribute, $value);
    }

    public function message()
    {
        return "The :attribute must be 'ANY' or a valid port or port range";
    }
}
