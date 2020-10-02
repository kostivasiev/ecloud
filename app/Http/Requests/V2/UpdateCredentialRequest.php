<?php

namespace App\Http\Requests\V2;

use UKFast\FormRequests\FormRequest;

class UpdateCredentialRequest extends FormRequest
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
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => ['sometimes', 'required', 'string'],
            'resource_id' => ['sometimes', 'nullable', 'string'],
            'host' => ['sometimes', 'nullable', 'string'],
            'user' => ['sometimes', 'required', 'string'],
            'password' => ['sometimes', 'required', 'string'],
            'port' => ['sometimes', 'nullable', 'integer'],
        ];
    }

    /**
     * Get the validation messages that apply to the request.
     *
     * @return array|string[]
     */
    public function messages()
    {
        return [
            'name.required' => 'The :attribute field, when specified, cannot be null',
            'user.required' => 'The :attribute field, when specified, cannot be null',
            'password.required' => 'The :attribute field, when specified, cannot be null',
        ];
    }
}
