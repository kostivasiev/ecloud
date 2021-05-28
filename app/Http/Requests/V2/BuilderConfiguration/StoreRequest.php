<?php

namespace App\Http\Requests\V2\BuilderConfiguration;

use UKFast\FormRequests\FormRequest;

class StoreRequest extends FormRequest
{
    public function rules()
    {
        return [
            'reseller_id' => [
                'sometimes',
                'required',
                'integer'
            ],
            'employee_id' => [
                'sometimes',
                'required',
                'integer'
            ],
            'data' => [
                'required',
                'json'
            ],
        ];
    }
}
