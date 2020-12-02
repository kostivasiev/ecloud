<?php

namespace App\Rules\V2;

use App\Models\V2\MrrCommitment;
use Illuminate\Contracts\Validation\Rule;

class CommitmentIsGreater implements Rule
{
    protected string $commitmentId;

    public function __construct(string $commitmentId)
    {
        $this->commitmentId = $commitmentId;
    }

    public function passes($attribute, $value)
    {
        $commitment = MrrCommitment::forUser(app('request')->user)
            ->findOrFail($this->commitmentId);
        return $value > $commitment->$attribute;
    }

    /**
     * @inheritDoc
     */
    public function message()
    {
        return "The :attribute value must be greater than the value it replaces";
    }
}
