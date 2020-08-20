<?php

namespace App\Rules\V2;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ExistsForUser implements Rule
{
    public function __construct($model)
    {
        $this->model = $model;
    }

    public function passes($attribute, $value)
    {
        try {
            $this->model::forUser(app('request')->user)->findOrFail($value);
        } catch (ModelNotFoundException $exception) {
            return false;
        }
        return true;
    }

    /**
     * @inheritDoc
     */
    public function message()
    {
        return 'The specified :attribute was not found';
    }
}
