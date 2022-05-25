<?php

namespace App\Http\Requests\V2\NetworkRule;

use App\Models\V2\NetworkPolicy;
use App\Models\V2\NetworkRulePort;
use App\Rules\V2\ExistsForUser;
use App\Rules\V2\IsPortWithinExistingRange;
use App\Rules\V2\IsResourceAvailable;
use App\Rules\V2\IsUniquePortOrRange;
use App\Rules\V2\ValidFirewallRulePortSourceDestination;
use App\Rules\V2\ValidFirewallRuleSourceDestination;
use Illuminate\Foundation\Http\FormRequest;

class Create extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'nullable|string|max:255',
            'network_policy_id' => [
                'bail',
                'required',
                'string',
                'exists:ecloud.network_policies,id,deleted_at,NULL',
                new ExistsForUser(NetworkPolicy::class),
                new IsResourceAvailable(NetworkPolicy::class),
            ],
            'sequence' => [
                'required',
                'integer',
                'max:5000'
            ],
            'source' => [
                'required',
                'string',
                new ValidFirewallRuleSourceDestination()
            ],
            'destination' => [
                'required',
                'string',
                new ValidFirewallRuleSourceDestination()
            ],
            'action' => [
                'required',
                'string',
                'in:ALLOW,DROP,REJECT'
            ],
            'direction' => 'required|string|in:IN,OUT,IN_OUT',
            'enabled' => 'required|boolean',
            'ports' => [
                'sometimes',
                'present',
                'array'
            ],
            'ports.*.protocol' => [
                'required',
                'string',
                'in:TCP,UDP,ICMPv4'
            ],
            'ports.*.source' => [
                'required_if:ports.*.protocol,TCP,UDP',
                'string',
                new ValidFirewallRulePortSourceDestination(),
            ],
            'ports.*.destination' => [
                'required_if:ports.*.protocol,TCP,UDP',
                'string',
                new ValidFirewallRulePortSourceDestination(),
            ]
        ];
    }

    /**
     * Get the validation messages that apply to the request.
     *
     * @return array|string[]
     */
    public function messages()
    {
        return [];
    }
}
