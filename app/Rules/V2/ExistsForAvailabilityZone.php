<?php

namespace App\Rules\V2;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ExistsForAvailabilityZone implements Rule
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
                $model::forUser(app('request')->user)->findOrFail($value);
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
