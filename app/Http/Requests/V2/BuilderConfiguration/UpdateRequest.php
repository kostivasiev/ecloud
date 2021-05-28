<?php

namespace App\Http\Requests\V2\BuilderConfiguration;

use UKFast\FormRequests\FormRequest;

class UpdateRequest extends FormRequest
{
    public function rules()
    {
        return [
            'reseller_id' => [
                'sometimes',
                'nullable',
                'integer'
            ],
            'employee_id' => [
                'sometimes',
                'nullable',
                'integer'
            ],
            'data' => [
                'sometimes',
                'required',
                'json'
            ],
        ];
    }
}
