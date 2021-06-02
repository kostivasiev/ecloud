<?php

namespace App\Rules\V2;

use App\Models\V2\HostGroup;
use Illuminate\Contracts\Validation\Rule;

class HasHosts implements Rule
{

    public function passes($attribute, $value)
    {
        $hostGroup = HostGroup::find($value);
        return $hostGroup && $hostGroup->hosts->count() > 0;
    }

    /**
     * @inheritDoc
     */
    public function message()
    {
        return 'There are no hosts assigned to the specified :attribute.';
    }
}