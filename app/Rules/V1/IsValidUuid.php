<?php

namespace App\Rules\V1;

use Illuminate\Contracts\Validation\Rule;

class IsValidUuid implements Rule
{

    public function passes($attribute, $value)
    {
        return \Ramsey\Uuid\Uuid::isValid($value);
    }

    /**
     * @inheritDoc
     */
    public function message()
    {
        return 'The :attribute is not a valid UUID';
    }
}
