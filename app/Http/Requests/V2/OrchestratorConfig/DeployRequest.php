<?php

namespace App\Http\Requests\V2\OrchestratorConfig;

use App\Models\V2\OrchestratorConfig;
use App\Rules\V2\ExistsForUser;
use UKFast\FormRequests\FormRequest;

class DeployRequest extends FormRequest
{
    public function rules()
    {
        return [
            'orchestrator_config_id' => [
                'required',
                'string',
                'exists:ecloud.orchestrator_configs,id,deleted_at,NULL',
                new ExistsForUser(OrchestratorConfig::class),
            ],
        ];
    }
}
