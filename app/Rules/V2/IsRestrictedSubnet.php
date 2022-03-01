<?php
namespace App\Rules\V2;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class IsRestrictedSubnet implements Rule
{
    protected $restricted = [
        '192.168.0.0/16',
        '10.255.255.0/24'
    ];

    public function passes($attribute, $value)
    {
        if (Auth::user()->isAdmin()) {
            return true;
        }
        $chosen = \IPLib\Factory::rangeFromString($value);
        foreach ($this->restricted as $item) {
            $restricted = \IPLib\Factory::rangeFromString($item);
            if ($restricted->containsRange($chosen)) {
                return false;
            }
        }
        return true;
    }

    public function message()
    {
        return 'The :attribute is in a restricted CIDR range';
    }
}
