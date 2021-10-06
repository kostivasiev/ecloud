<?php

namespace App\Rules\V2\IpAddress;

use App\Models\V2\IpAddress;
use Illuminate\Contracts\Validation\Rule;

class IsClusterType implements Rule
{
    public function passes($attribute, $value)
    {
        return (IpAddress::find($value))->type == IpAddress::TYPE_CLUSTER;
    }

    /**
     * @inheritDoc
     */
    public function message()
    {
        return 'The :attribute must be of type ' . IpAddress::TYPE_CLUSTER;
    }
}
