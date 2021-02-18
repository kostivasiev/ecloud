<?php

namespace App\Rules\V2;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;

class ExistsForUser implements Rule
{
    public function __construct($model)
    {
        $this->model = $model;
    }

    public function passes($attribute, $value)
    {
        if (!is_array($this->model)) {
            $this->model = [$this->model];
        }

        foreach ($this->model as $model) {
            try {
                $model::forUser(Auth::user())->findOrFail($value);
                return true;
            } catch (ModelNotFoundException $exception) {
                continue;
            }
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function message()
    {
        return 'The specified :attribute was not found';
    }
}
