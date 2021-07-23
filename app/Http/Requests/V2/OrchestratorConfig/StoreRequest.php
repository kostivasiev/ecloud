<?php

namespace App\Http\Requests\V2\OrchestratorConfig;

use Carbon\Carbon;
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
            'deploy_on' => [
                'sometimes',
                'required',
                'date_format:Y-m-d H:i:s',
                'after:now',
            ]
        ];
    }
}
