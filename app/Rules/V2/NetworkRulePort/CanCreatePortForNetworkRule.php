<?php

namespace App\Rules\V2\NetworkRulePort;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\NetworkRule;
use App\Models\V2\NetworkRulePort;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class CanCreatePortForNetworkRule implements Rule
{
    public function passes($attribute, $value)
    {
        $networkRule = NetworkRule::forUser(Auth::user())->find($value);

        return !in_array($networkRule->type, [
            NetworkRule::TYPE_DHCP,
            NetworkRule::TYPE_CATCHALL
        ]);
    }

    /**
     * @inheritDoc
     */
    public function message()
    {
        return 'Cannot create port for :attribute';
    }
}
