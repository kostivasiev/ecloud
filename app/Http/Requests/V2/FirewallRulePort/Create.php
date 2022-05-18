<?php

namespace App\Http\Requests\V2\FirewallRulePort;

use App\Models\V2\FirewallRule;
use App\Models\V2\FirewallRulePort;
use App\Rules\V2\ExistsForUser;
use App\Rules\V2\IsPortWithinExistingRange;
use App\Rules\V2\IsUniquePortOrRange;
use App\Rules\V2\ValidFirewallRulePortSourceDestination;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Request;

class Create extends FormRequest
{
    /**
     * @return array
     */
    public function rules()
    {
        $firewallRuleId = Request::input('firewall_rule_id');
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
                'nullable',
                new ValidFirewallRulePortSourceDestination(),
                new IsUniquePortOrRange(FirewallRulePort::class, $firewallRuleId),
                new IsPortWithinExistingRange(FirewallRulePort::class, $firewallRuleId),
            ],
            'destination' => [
                'required_if:protocol,TCP,UDP',
                'string',
                'nullable',
                new ValidFirewallRulePortSourceDestination(),
                new IsUniquePortOrRange(FirewallRulePort::class, $firewallRuleId),
                new IsPortWithinExistingRange(FirewallRulePort::class, $firewallRuleId),
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
