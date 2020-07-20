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
            'name' => 'required|string|max:50'
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
        ];
    }
}
