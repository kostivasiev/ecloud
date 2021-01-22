<?php

namespace App\Rules\V2\RouterThroughput;

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
        exit(print_r(
            app()->request->
        ));

            exit(print_r(
                [
                    $attribute, $value, $this->model
                ]
            ));



    }

    /**
     * @inheritDoc
     */
    public function message()
    {
        return 'The specified :attribute was not found';
    }
}
