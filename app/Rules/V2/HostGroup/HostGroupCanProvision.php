<?php

namespace App\Rules\V2\HostGroup;

use App\Models\V2\HostGroup;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class HostGroupCanProvision implements Rule
{
    public function __construct($ram)
    {
        $this->ram = $ram;
    }

    public function passes($attribute, $value)
    {
        $hostGroup = HostGroup::forUser(Auth::user())->findOrFail($value);

        return $hostGroup->canProvision($this->ram);
    }

    /**
     * @inheritDoc
     */
    public function message()
    {
        return 'TThe specified host group has insufficient resources.';
    }
}
