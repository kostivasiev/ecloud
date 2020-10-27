<?php

namespace App\Http\Requests\V2;

use App\Models\V2\Router;
use App\Rules\V2\ExistsForUser;
use App\Rules\V2\ValidCidrSubnetArray;
use UKFast\FormRequests\FormRequest;

class UpdateFirewallRuleRequest extends FormRequest
{
    /**
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'sometimes|required|string|max:50',
            'router_id' => [
                'sometimes',
                'required',
                'string',
                'exists:ecloud.routers,id,deleted_at,NULL',
                new ExistsForUser(Router::class)
            ],
            'firewall_policy_id' => 'sometimes|required|string|exists:firewall_policies,id,deleted_at,NULL',
            'source' => [
                'sometimes',
                'required',
                'string',
                new ValidCidrSubnetArray()
            ],
            'destination' => [
                'sometimes',
                'required',
                'string',
                new ValidCidrSubnetArray()
            ],
            'action' => 'sometimes|required|string|in:ALLOW,DROP,REJECT',
            'direction' => 'sometimes|required|string|in:IN,OUT,IN_OUT',
            'enabled' => 'sometimes|required|boolean|default:0',
        ];
    }

    /**
     * @return array
     */
    public function messages()
    {
        return [
            'name.required' => 'The :attribute field, when specified, cannot be null',
            'name.string' => 'The :attribute field must contain a string',
            'name.max' => 'The :attribute field must be less than 50 characters',
            'router_id.required' => 'The :attribute field, when specified, cannot be null',
            'router_id.exists' => 'The specified :attribute was not found',
        ];
    }
}
