<?php

namespace App\Http\Requests\V2\FirewallRulePort;

use App\Models\V2\FirewallRule;
use App\Rules\V2\ExistsForUser;
use App\Rules\V2\ValidFirewallRulePortSourceDestination;
use Illuminate\Validation\Rules\RequiredIf;
use UKFast\FormRequests\FormRequest;

class Create extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            'name' => [
                'nullable',
                'string',
                'max:255'
            ],
            'firewall_rule_id' => [
                'required',
                'string',
                'exists:ecloud.firewall_rules,id,deleted_at,NULL',
                new ExistsForUser(FirewallRule::class)
            ],
            'protocol' => [
                'required',
                'string',
                'in:TCP,UDP,ICMPv4'
            ],
            'source' => [
                'required_if:protocol,TCP,UDP',
                'string',
                new ValidFirewallRulePortSourceDestination()
            ],
            'destination' => [
                'required_if:protocol,TCP,UDP',
                'string',
                new ValidFirewallRulePortSourceDestination()
            ]
        ];
    }

    /**
     * @return array
     */
    public function messages()
    {
        return [
            'required' => 'The :attribute field is required',
            'exists' => 'The specified :attribute was not found',
            'protocol.in' => 'The :attribute field must contain one of TCP, UDP or ICMPv4',
        ];
    }
}
