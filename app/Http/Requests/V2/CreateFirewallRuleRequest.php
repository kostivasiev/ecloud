<?php

namespace App\Http\Requests\V2;

use App\Models\V2\Router;
use App\Rules\V2\ExistsForUser;
use App\Rules\V2\ValidCidrSubnetArray;
use UKFast\FormRequests\FormRequest;

class CreateFirewallRuleRequest extends FormRequest
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
            'name' => 'nullable|string|max:50',
            'router_id' => [
                'required',
                'string',
                'exists:ecloud.routers,id,deleted_at,NULL',
                new ExistsForUser(Router::class)
            ],
            'firewall_policy_id' => 'required|string|exists:firewall_policies,id,deleted_at,NULL',
            'source' => [
                'required',
                'string',
                new ValidCidrSubnetArray()
            ],
            'destination' => [
                'required',
                'string',
                new ValidCidrSubnetArray()
            ],
            'action' => 'required|string|in:ALLOW,DROP,REJECT',
            'direction' => 'required|string|in:IN,OUT,IN_OUT',
            'enabled' => 'required|boolean|default:0',
        ];
    }

    /**
     * @return array
     */
    public function messages()
    {
        return [
            'name.string' => 'The :attribute field must contain a string',
            'name.max' => 'The :attribute field must be less than 50 characters',
            'router_id.required' => 'The :attribute field is required',
            'router_id.exists' => 'The specified :attribute was not found',
            'firewall_policy_id.required' => 'The :attribute field is required',
            'firewall_policy_id.exists' => 'The specified :attribute was not found',
            'source.required' => 'The :attribute field is required',
            'source.string' => 'The :attribute field must contain a string',
            'destination.required' => 'The :attribute field is required',
            'destination.string' => 'The :attribute field must contain a string',
            'action.required' => 'The :attribute field is required',
            'action.string' => 'The :attribute field must contain a string',
            'action.in' => 'The :attribute field contains an invalid option',
            'direction.required' => 'The :attribute field is required',
            'direction.string' => 'The :attribute field must contain a string',
            'direction.in' => 'The :attribute field contains an invalid option',
            'enabled.required' => 'The :attribute field is required',
            'enabled.boolean' => 'The :attribute field is not a valid boolean value',
        ];
    }
}
