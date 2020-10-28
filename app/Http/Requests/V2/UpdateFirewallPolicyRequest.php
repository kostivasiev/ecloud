<?php

namespace App\Http\Requests\V2;

use App\Models\V2\Router;
use App\Rules\V2\ExistsForUser;
use UKFast\FormRequests\FormRequest;

class UpdateFirewallPolicyRequest extends FormRequest
{
    /**
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'sometimes|required|string|max:50',
            'sequence' => 'sometimes|required|integer',
            'router_id' => [
                'sometimes',
                'required',
                'string',
                'exists:ecloud.routers,id,deleted_at,NULL',
                new ExistsForUser(Router::class)
            ],
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
            'sequence.required' => 'The :attribute field, when specified, cannot be null',
            'sequence.integer' => 'The specified :attribute must be an integer',
            'router_id.required' => 'The :attribute field, when specified, cannot be null',
            'router_id.exists' => 'The specified :attribute was not found',
        ];
    }
}
