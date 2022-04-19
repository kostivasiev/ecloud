<?php

namespace App\Http\Requests\V2\OrchestratorConfig;

use App\Models\V2\OrchestratorConfig;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    protected string $resellerRequired;

    public function rules()
    {
        $orchestratorConfig = OrchestratorConfig::forUser(Auth::user())
            ->findOrFail(Request::route('orchestratorConfigId'));
        $this->resellerRequired = 'sometimes';
        if ($this->request->has('deploy_on')) {
            if (!$orchestratorConfig->reseller_id || !$this->request->has('reseller_id')) {
                $this->resellerRequired = 'required';
            }
        }

        return [
            'reseller_id' => [
                $this->resellerRequired,
                ($this->resellerRequired == 'required') ? '' : 'nullable',
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
            'locked' => [
                'sometimes',
                'required',
                'boolean',
            ],
        ];
    }

    public function messages()
    {
        $messages = [];
        if ($this->resellerRequired == 'required') {
            $messages['reseller_id.required'] = 'The :attribute is required when specifying deploy on property';
        }
        return $messages;
    }
}
