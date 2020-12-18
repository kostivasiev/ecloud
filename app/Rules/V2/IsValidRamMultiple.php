<?php

namespace App\Rules\V2;

use Illuminate\Contracts\Validation\Rule;

/**
 * Class IsValidRamMultiple
 * @package App\Rules\V2
 */
class IsValidRamMultiple implements Rule
{
    public function passes($attribute, $value)
    {
        return $value % 8 === 0;
    }

    /**
     * @inheritDoc
     */
    public function message()
    {
        return ':attribute must be a valid MiB multiple/';
    }
}
