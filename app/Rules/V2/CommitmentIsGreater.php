<?php

namespace App\Rules\V2;

use App\Models\V2\DiscountPlan;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class CommitmentIsGreater implements Rule
{
    protected string $discountPlanId;

    public function __construct(string $discountPlanId)
    {
        $this->discountPlanId = $discountPlanId;
    }

    public function passes($attribute, $value)
    {
        $discountPlan = DiscountPlan::forUser(Auth::user())
            ->findOrFail($this->discountPlanId);
        return $value > $discountPlan->$attribute;
    }

    /**
     * @inheritDoc
     */
    public function message()
    {
        return "The :attribute value must be greater than the value it replaces";
    }
}
