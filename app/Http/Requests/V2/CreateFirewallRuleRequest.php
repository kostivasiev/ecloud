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
            'firewall_policy_id' => [
                'required',
                'string',
                'exists:ecloud.firewall_policies,id,deleted_at,NULL',
                new ExistsForUser(FirewallPolicy::class)
            ],
            'service_type' => 'required|string|in:TCP,UDP',
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
            'required' => 'The :attribute field is required',
            'string' => 'The :attribute field must contain a string',
            'name.max' => 'The :attribute field must be less than 50 characters',
            'firewall_policy_id.exists' => 'The specified :attribute was not found',
            'service_type.in' => 'The :attribute field must contain one of IP,IGMP,ICMPv4,ALG,TCP,UDP or ICMPv6',
            'action.in' => 'The :attribute field contains an invalid option',
            'direction.in' => 'The :attribute field contains an invalid option',
            'enabled.boolean' => 'The :attribute field is not a valid boolean value',
        ];
    }
}
