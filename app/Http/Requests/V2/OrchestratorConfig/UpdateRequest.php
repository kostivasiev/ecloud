<?php

namespace App\Http\Requests\V2\OrchestratorConfig;

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
            'deploy_on' => [
                'sometimes',
                'required',
                'date_format:Y-m-d H:i:s',
                'after:now',
            ],
            'data' => [
                'sometimes',
                'required',
                'json'
            ],
        ];
    }
}
