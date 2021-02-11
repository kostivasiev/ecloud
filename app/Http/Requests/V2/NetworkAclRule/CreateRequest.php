<?php

namespace App\Http\Requests\V2\NetworkAclRule;

use App\Models\V2\NetworkAclPolicy;
use App\Rules\V2\ExistsForUser;
use App\Rules\V2\ValidFirewallRuleSourceDestination;
use UKFast\FormRequests\FormRequest;

class CreateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'nullable|string|max:50',
            'network_acl_policy_id' => [
                'required',
                'string',
                'exists:ecloud.network_acl_policies,id,deleted_at,NULL',
                new ExistsForUser(NetworkAclPolicy::class),
            ],
            'sequence' => 'required|integer',
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
            'action' => 'required|string|in:ALLOW,DROP,REJECT',
            'enabled' => 'required|boolean',
        ];
    }

    /**
     * Get the validation messages that apply to the request.
     *
     * @return array|string[]
     */
    public function messages()
    {
        return [
            'router_id.required' => 'The :attribute field is required',
            'subnet.unique' => 'The :attribute is already assigned to another network',
        ];
    }
}
