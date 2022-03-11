<?php

namespace App\Http\Requests\V2\OrchestratorConfig;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    public function rules()
    {
        $resellerRequired = 'sometimes';
        if ($this->request->has('deploy_on')) {
            $resellerRequired = 'required';
        }
        return [
            'reseller_id' => [
                $resellerRequired,
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

    public function messages()
    {
        $messages = [];
        if ($this->request->has('deploy_on')) {
            $messages['reseller_id.required'] = 'The :attribute is required when specifying deploy on property';
        }
        return $messages;
    }
}
