<?php

namespace App\Http\Requests\V2;

use App\Models\V2\FirewallPolicy;
use App\Models\V2\Router;
use App\Rules\V2\ExistsForUser;
use App\Rules\V2\ValidCidrSubnetArray;
use App\Rules\V2\ValidPortReference;
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
            'firewall_policy_id' => [
                'required',
                'string',
                'exists:ecloud.firewall_policies,id,deleted_at,NULL',
                new ExistsForUser(FirewallPolicy::class)
            ],
            'service_type' => 'required|string|in:IP,IGMP,ICMPv4,ALG,TCP,UDP,ICMPv6',
            'source' => [
                'required',
                'string',
                new ValidCidrSubnetArray()
            ],
            'source_ports' => [
                'required',
                'string',
                new ValidPortReference()
            ],
            'destination' => [
                'required',
                'string',
                new ValidCidrSubnetArray()
            ],
            'destination_ports' => [
                'required',
                'string',
                new ValidPortReference()
            ],
            'action' => 'required|string|in:ALLOW,DROP,REJECT',
            'direction' => 'required|string|in:IN,OUT,IN_OUT',
            'enabled' => 'required|boolean',
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
            'source_ports.required' => 'The :attribute field is required',
            'source_ports.string' => 'The :attribute field must contain a string',
            'service_type.required' => 'The :attribute field is required',
            'service_type.string' => 'The :attribute field must contain a string',
            'service_type.in' => 'The :attribute field must contain one of IP,IGMP,ICMPv4,ALG,TCP,UDP or ICMPv6',
            'destination.required' => 'The :attribute field is required',
            'destination.string' => 'The :attribute field must contain a string',
            'destination_ports.required' => 'The :attribute field is required',
            'destination_ports.string' => 'The :attribute field must contain a string',
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
