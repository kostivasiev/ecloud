<?php

namespace App\Http\Requests\V2\FirewallRulePort;

use App\Models\V2\FirewallRulePort;
use App\Rules\V2\IsPortWithinExistingRange;
use App\Rules\V2\IsUniquePortOrRange;
use App\Rules\V2\ValidFirewallRulePortSourceDestination;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Request;

class Update extends FormRequest
{
    /**
     * @return array
     */
    public function rules()
    {
        $firewallRule = (FirewallRulePort::findOrFail(Request::route('firewallRulePortId')))
            ->firewallRule;
        return [
            'name' => [
                'sometimes',
                'nullable',
                'string',
                'max:255'
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
                'nullable',
                new ValidFirewallRulePortSourceDestination(),
                new IsUniquePortOrRange(FirewallRulePort::class, $firewallRule->id),
                new IsPortWithinExistingRange(FirewallRulePort::class, $firewallRule->id),
            ],
            'destination' => [
                'required_if:protocol,TCP,UDP',
                'string',
                'nullable',
                new ValidFirewallRulePortSourceDestination(),
                new IsUniquePortOrRange(FirewallRulePort::class, $firewallRule->id),
                new IsPortWithinExistingRange(FirewallRulePort::class, $firewallRule->id),
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
