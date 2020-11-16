<?php

namespace App\Rules\V2;

use Illuminate\Contracts\Validation\Rule;

class ValidPortReference implements Rule
{
    public function passes($attribute, $value)
    {
        foreach (explode(",", $value) as $port) {
            if (strpos($port, '-')) {
                if (!preg_match('/\d+\-\d+/', $port)) {
                    return false;
                }
            }
            if (!preg_match('/\d+/', $port)) {
                return false;
            }
        }
        return true;
    }

    public function message()
    {
        return 'The :attribute must be a valid port or port range';
    }
}
