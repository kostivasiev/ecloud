<?php
namespace App\Http\Requests\V2;

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
            'name' => 'required|string|max:50',
            'router_id' => 'required|string|exists:ecloud.routers,id,deleted_at,NULL',
        ];
    }

    /**
     * @return array
     */
    public function messages()
    {
        return [
            'name.required' => 'The :attribute field is required',
            'name.string' => 'The :attribute field must contain a string',
            'name.max' => 'The :attribute field must be less than 50 characters',
            'router_id.required' => 'The :attribute field is required',
            'router_id.exists' => 'The specified :attribute was not found',
        ];
    }
}
