<?php

namespace App\Rules\V2;

use Illuminate\Contracts\Validation\Rule;
use IPLib\Range\Subnet;

/**
 * Class IsSubnetBigEnough
 * @package App\Rules\V2
 */
class IsSubnetBigEnough implements Rule
{
    public function passes($attribute, $value)
    {
        $subnet = Subnet::fromString($value);
        return ($subnet) ? $subnet->getNetworkPrefix() < 30 : false;
    }

    /**
     * @inheritDoc
     */
    public function message()
    {
        return 'The :attribute network size is too small and must be larger than /30';
    }
}
