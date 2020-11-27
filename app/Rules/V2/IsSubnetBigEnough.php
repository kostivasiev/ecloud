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
        return $subnet->getNetworkPrefix() < 30;
    }

    /**
     * @inheritDoc
     */
    public function message()
    {
        return 'The range in :attribute is too small and must be greater than or equal to 30';
    }
}
