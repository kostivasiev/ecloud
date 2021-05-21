<?php

namespace App\Http\Requests\V2\NetworkRule;

use App\Models\V2\NetworkPolicy;
use App\Rules\V2\ExistsForUser;
use App\Rules\V2\IsResourceAvailable;
use App\Rules\V2\ValidFirewallRuleSourceDestination;
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
            'network_policy_id' => [
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
