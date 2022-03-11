<?php

namespace App\Http\Requests\V2;

use App\Models\V2\Router;
use App\Rules\V2\ExistsForUser;
use App\Rules\V2\IsResourceAvailable;
use Illuminate\Foundation\Http\FormRequest;

class CreateFirewallPolicyRequest extends FormRequest
{
    /**
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'nullable|string|max:255',
            'sequence' => 'required|integer',
            'router_id' => [
                'required',
                'string',
                'exists:ecloud.routers,id,deleted_at,NULL',
                new ExistsForUser(Router::class),
                new IsResourceAvailable(Router::class),
            ]
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
            'sequence.required' => 'The :attribute field is required',
            'sequence.integer' => 'The specified :attribute must be an integer',
            'router_id.required' => 'The :attribute field is required',
            'router_id.exists' => 'The specified :attribute was not found',
        ];
    }
}
