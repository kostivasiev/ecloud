<?php

namespace App\Http\Requests\V2\NetworkRulePort;

use App\Models\V2\NetworkRule;
use App\Rules\V2\ExistsForUser;
use App\Rules\V2\ValidPortReference;
use UKFast\FormRequests\FormRequest;

class Update extends FormRequest
{
    public function rules()
    {
        return [
            'name' => 'sometimes|nullable|string|max:50',
            'network_rule_id' => [
                'sometimes',
                'required',
                'string',
                'exists:ecloud.network_rules,id,deleted_at,NULL',
                new ExistsForUser(NetworkRule::class),
            ],
            'protocol' => [
                'sometimes',
                'required',
                'string',
                'in:TCP,UDP,ICMPv4'
            ],
            'source' => [
                'required_if:protocol,TCP,UDP',
                'string',
                new ValidPortReference()
            ],
            'destination' => [
                'required_if:protocol,TCP,UDP',
                'string',
                new ValidPortReference()
            ],
        ];
    }
}