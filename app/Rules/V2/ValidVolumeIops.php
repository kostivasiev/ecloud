<?php

namespace App\Rules\V2;

use App\Models\V2\Volume;
use Illuminate\Contracts\Validation\Rule;

class ValidVolumeIops implements Rule
{
    public function passes($attribute, $value)
    {
        return (in_array($value, array_keys(Volume::$iopsValues)));
    }

    public function message()
    {
        return 'The :attribute does not contain a valid iops value';
    }
}
