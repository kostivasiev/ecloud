<?php

namespace App\Http\Requests\V2\FirewallRulePort;

use App\Models\V2\FirewallRule;
use App\Models\V2\FirewallRulePort;
use App\Rules\V2\ExistsForUser;
use App\Rules\V2\FirewallRulePort\UniquePortListRule;
use App\Rules\V2\FirewallRulePort\UniquePortRule;
use App\Rules\V2\ValidFirewallRulePortSourceDestination;
use Illuminate\Foundation\Http\FormRequest;

class Create extends FormRequest
{
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
                new ExistsForUser(FirewallRule::class),
            ],
            'protocol' => [
                'required',
                'string',
                'in:TCP,UDP,ICMPv4'
            ],
            'source' => [
                'required_if:protocol,TCP,UDP',
                'string',
                'nullable',
                new ValidFirewallRulePortSourceDestination(),
                new UniquePortRule(FirewallRulePort::class),
                new UniquePortListRule(FirewallRulePort::class),
            ],
            'destination' => [
                'required_if:protocol,TCP,UDP',
                'string',
                'nullable',
                new ValidFirewallRulePortSourceDestination(),
                new UniquePortRule(FirewallRulePort::class),
                new UniquePortListRule(FirewallRulePort::class),
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
