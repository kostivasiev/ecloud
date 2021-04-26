<?php

namespace App\Rules\V2;

use App\Models\V2\Vpc;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class IsMaxVpcLimitReached implements Rule
{
    public function passes($attribute, $value)
    {
        $reseller_bypass = [
            7052, // UKFast - eCloud Testing
            22114, // UKFast - eCloud Automated Testing
        ];

        if (in_array(Auth::user()->resellerId(), $reseller_bypass)) {
            return true;
        }
        return (Vpc::forUser(Auth::user())->get()->count() < config('defaults.vpc.max_count'));
    }

    public function message()
    {
        return 'The maximum number of ' . config('defaults.vpc.max_count') . ' VPCs has been reached';
    }
}
