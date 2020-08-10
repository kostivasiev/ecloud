<?php
namespace App\Http\Requests\V2;

use UKFast\FormRequests\FormRequest;

class UpdateFirewallRuleRequest extends FormRequest
{
    /**
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'sometimes|required|string|max:50'
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
        ];
    }
}
