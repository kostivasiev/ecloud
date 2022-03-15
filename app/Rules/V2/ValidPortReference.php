<?php

namespace App\Rules\V2;

use Illuminate\Contracts\Validation\Rule;

class ValidPortReference implements Rule
{
    /**
     * Validate the value is a port / port range:
     * - Port (80)
     * - Port range (80-90)
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        if (empty($value)) {
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
            if (preg_match('/[\s\.]+/', $port)) {
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
