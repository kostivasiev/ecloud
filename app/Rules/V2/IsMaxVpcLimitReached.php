<?php

namespace App\Rules\V2;

use App\Models\V2\Vpc;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class IsMaxVpcLimitReached implements Rule
{
    private int $vpcMaxLimit;

    public function __construct()
    {
        $this->vpcMaxLimit = config('defaults.vpc.max_count');
    }

    public function passes($attribute, $value)
    {
        return (Vpc::forUser(Auth::user())->get()->count() < $this->vpcMaxLimit);
    }

    public function message()
    {
        return 'The maximum number of ' . $this->vpcMaxLimit . ' VPCs has been reached';
    }
}
