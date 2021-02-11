<?php

namespace App\Http\Requests\V2\NetworkAclRule;

use App\Models\V2\NetworkAclPolicy;
use App\Rules\V2\ExistsForUser;
use App\Rules\V2\ValidFirewallRuleSourceDestination;
use UKFast\FormRequests\FormRequest;

class UpdateRequest extends FormRequest
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
            'name' => 'sometimes|nullable|string|max:50',
            'network_acl_policy_id' => [
                'sometimes',
                'required',
                'string',
                'exists:ecloud.network_acl_policies,id,deleted_at,NULL',
                new ExistsForUser(NetworkAclPolicy::class),
            ],
            'sequence' => 'sometimes|required|integer',
            'source' => [
                'sometimes',
                'required',
                'string',
                new ValidFirewallRuleSourceDestination()
            ],
            'destination' => [
                'sometimes',
                'required',
                'string',
                new ValidFirewallRuleSourceDestination()
            ],
            'action' => 'sometimes|required|string|in:ALLOW,DROP,REJECT',
            'enabled' => 'sometimes|required|boolean',
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
