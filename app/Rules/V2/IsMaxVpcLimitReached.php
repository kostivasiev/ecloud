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
        $this->vpcMaxLimit = config('defaults.vpc.max_count', 20);
    }

    public function passes($attribute, $value)
    {
        $vpc = Vpc::forUser(Auth::user())->get();
        return ($vpc->count() < $this->vpcMaxLimit);
    }

    public function message()
    {
        return 'The maximum number of Vpc instances has been reached';
    }
}