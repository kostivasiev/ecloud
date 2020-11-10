<?php

namespace App\Rules\V2;

use Illuminate\Contracts\Validation\Rule;
use IPLib\Address\IPv4;

/**
 * Class ValidIpv4
 * @package App\Rules\V2
 */
class ValidIpv4 implements Rule
{
    public function passes($attribute, $value)
    {
        $ipAddress = IPv4::fromString($value);
        return !is_null($ipAddress);
    }

    /**
     * @inheritDoc
     */
    public function message()
    {
        return 'The :attribute must be a valid IPv4 address';
    }
}
