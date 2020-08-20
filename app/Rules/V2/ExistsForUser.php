<?php

namespace App\Rules\V2;

use Illuminate\Contracts\Validation\Rule;

class ExistsForUser implements Rule
{
    public function __construct($model)
    {
        $this->model = $model;
    }

    public function passes($attribute, $value)
    {
        $this->model::forUser(app('request')->user)->findOrFail($value);
        return true;
    }

    /**
     * @inheritDoc
     */
    public function message()
    {
        return 'The :attribute was not found';
    }
}
