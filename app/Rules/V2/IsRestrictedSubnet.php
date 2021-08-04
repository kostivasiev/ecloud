<?php
namespace App\Rules\V2;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class IsRestrictedSubnet implements Rule
{
    public function passes($attribute, $value)
    {
        if (Auth::user()->isAdmin()) {
            return true;
        }
        $restricted = \IPLib\Factory::rangeFromString('192.168.0.0/16');
        $chosen = \IPLib\Factory::rangeFromString($value);

        return !$restricted->containsRange($chosen);
    }

    public function message()
    {
        return 'The :attribute is in a restricted CIDR range';
    }
}
