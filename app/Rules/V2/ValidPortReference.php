<?php

namespace App\Rules\V2;

use Illuminate\Contracts\Validation\Rule;

class ValidPortReference implements Rule
{
    protected $isIcmp = false;

    public function __construct()
    {
        $request = app('request');
        if ($request->has('protocol') && $request->input('protocol') == 'ICMPv4') {
            $this->isIcmp = true;
        }
    }

    public function passes($attribute, $value)
    {
        if ($this->isIcmp) {
            if (empty($value)) {
                return true;
            }
            return false;
        }
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
        if ($this->isIcmp) {
            return 'When using ICMPv4 protocol :attribute must be null';
        }
        return 'The :attribute must be a valid port or port range';
    }
}
