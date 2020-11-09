<?php

namespace App\Http\Requests\V2\FirewallRule;

use App\Models\V2\FirewallPolicy;
use App\Rules\V2\ExistsForUser;
use App\Rules\V2\ValidIpFormatCsvString;
use App\Rules\V2\ValidPortReference;
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
        $firewallPortRules = (new \App\Http\Requests\V2\FirewallRulePort\Create)->rules();

        return [
            'name' => 'nullable|string|max:50',
            'sequence' => 'required|integer',
            'firewall_policy_id' => [
                'required',
                'string',
                'exists:ecloud.firewall_policies,id,deleted_at,NULL',
                new ExistsForUser(FirewallPolicy::class)
            ],
            'source' => [
                'nullable',
                'string',
                new ValidIpFormatCsvString()
            ],
            'destination' => [
                'nullable',
                'string',
                new ValidIpFormatCsvString()
            ],
            'action' => 'required|string|in:ALLOW,DROP,REJECT',
            'direction' => 'required|string|in:IN,OUT,IN_OUT',
            'enabled' => 'required|boolean',
            'ports' => [
                'sometimes',
                'required',
                'array'
            ],
            'ports.*.protocol' => $firewallPortRules['protocol'],
            'ports.*.source' => $firewallPortRules['source'],
            'ports.*.destination' => $firewallPortRules['destination']
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
            'firewall_policy_id.exists' => 'The specified :attribute was not found',
            'in' => 'The :attribute field contains an invalid option',
            'enabled.boolean' => 'The :attribute field is not a valid boolean value',
            'sequence.integer' => 'The specified :attribute must be an integer',
        ];
    }
}
