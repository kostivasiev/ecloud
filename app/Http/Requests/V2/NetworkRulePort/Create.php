<?php

namespace App\Http\Requests\V2\NetworkRulePort;

use App\Models\V2\NetworkRule;
use App\Rules\V2\ExistsForUser;
use App\Rules\V2\NetworkRulePort\CanCreatePortForNetworkRule;
use App\Rules\V2\ValidPortReference;
use Illuminate\Foundation\Http\FormRequest;

class Create extends FormRequest
{
    public function rules()
    {
        return [
            'name' => 'nullable|string|max:255',
            'network_rule_id' => [
                'bail',
                'required',
                'string',
                'exists:ecloud.network_rules,id,deleted_at,NULL',
                new ExistsForUser(NetworkRule::class),
                new CanCreatePortForNetworkRule()
            ],
            'protocol' => [
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
