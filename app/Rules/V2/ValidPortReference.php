<?php

namespace App\Rules\V2;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Str;

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

        // Remove white space & explode
        return Str::of($value)->split('/[\s,]+/')
                ->filter(function ($item) {
                    // validate port or port range
                    $result = preg_match('/^[0-9]+-?(?:(?<=-)[0-9]+|\b)$/', $item, $matches);
                    if ($result) {
                        if (preg_match('/\-/', $matches[0])) {
                            $ports = explode('-', $matches[0]);
                            return !($this->validPort($ports[0]) && $this->validPort($ports[1]));
                        }
                        return !($this->validPort($matches[0]));
                    }
                    return true;
                })->count() < 1;
    }

    public function message()
    {
        return 'The :attribute must be a valid port or port range';
    }

    public function validPort(string|int $port): bool
    {
        return ((int) $port >= 1 && (int) $port <= 65535);
    }
}
