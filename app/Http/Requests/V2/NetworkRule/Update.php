<?php

namespace App\Http\Requests\V2\NetworkRule;

use App\Rules\V2\ValidFirewallRuleSourceDestination;
use UKFast\FormRequests\FormRequest;

class Update extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'sometimes|nullable|string|max:50',
            'sequence' => [
                'sometimes',
                'required',
                'integer',
                'max:5000'
            ],
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
        return [];
    }
}
