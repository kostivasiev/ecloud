<?php

namespace App\Http\Requests\V2\FirewallRule;

use App\Models\V2\FirewallPolicy;
use App\Models\V2\FirewallRule;
use App\Rules\V2\ExistsForUser;
use App\Rules\V2\FirewallRulePort\ValidPortArrayRule;
use App\Rules\V2\ValidateIpTypesAreConsistent;
use App\Rules\V2\ValidFirewallRulePortSourceDestination;
use App\Rules\V2\ValidFirewallRuleSourceDestination;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class Update extends FormRequest
{
    /**
     * @return array
     */
    public function rules()
    {
        $firewallRule = FirewallRule::foruser(Auth::user())
            ->findOrFail(Request::route('firewallRuleId'));

        return [
            'name' => 'sometimes|required|string|max:255',
            'sequence' => 'sometimes|required|integer',
            'source' => [
                'sometimes',
                'required',
                'string',
                new ValidFirewallRuleSourceDestination(),
                new ValidateIpTypesAreConsistent(Request::input('destination', $firewallRule->destination)),
            ],
            'destination' => [
                'sometimes',
                'required',
                'string',
                new ValidFirewallRuleSourceDestination(),
                new ValidateIpTypesAreConsistent(Request::input('source', $firewallRule->source)),
            ],
            'action' => 'sometimes|required|string|in:ALLOW,DROP,REJECT',
            'direction' => 'sometimes|required|string|in:IN,OUT,IN_OUT',
            'enabled' => 'sometimes|required|boolean',
            'ports' => [
                'sometimes',
                'present',
                'array',
                new ValidPortArrayRule(),
            ],
            'ports.*.protocol' => [
                'required',
                'string',
                'in:TCP,UDP,ICMPv4'
            ],
            'ports.*.source' => [
                'required_if:ports.*.protocol,TCP,UDP',
                'string',
                'nullable',
                new ValidFirewallRulePortSourceDestination()
            ],
            'ports.*.destination' => [
                'required_if:ports.*.protocol,TCP,UDP',
                'string',
                'nullable',
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
            'string' => 'The :attribute field must contain a string',
            'name.max' => 'The :attribute field must be less than 50 characters',
            'in' => 'The :attribute field contains an invalid option',
            'service_type.in' => 'The :attribute field must contain one of TCP or UDP',
            'enabled.boolean' => 'The :attribute field is not a valid boolean value',
            'sequence.integer' => 'The specified :attribute must be an integer',
        ];
    }
}
