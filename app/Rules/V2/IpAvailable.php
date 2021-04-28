<?php

namespace App\Rules\V2;

use App\Models\V2\Nic;
use Illuminate\Contracts\Validation\Rule;

class IpAvailable implements Rule
{
    public function __construct($networkId)
    {
        $this->networkId = $networkId;
    }

    public function passes($attribute, $value)
    {
        return Nic::where('network_id', $this->networkId)
                ->where('ip_address', $value)
                ->count() == 0;
    }

    /**
     * @inheritDoc
     */
    public function message()
    {
        return 'The specified :attribute is already assigned.';
    }
}
