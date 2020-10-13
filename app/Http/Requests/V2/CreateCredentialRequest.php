<?php

namespace App\Http\Requests\V2;

use UKFast\FormRequests\FormRequest;

class CreateCredentialRequest extends FormRequest
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
            'name' => ['nullable', 'string'],
            'resource_id' => ['required', 'string'],
            'host' => ['nullable', 'string'],
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
            'port' => ['nullable', 'integer'],
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
            'username.required' => 'The :attribute field is required',
            'resource_id.required' => 'The :attribute field is required',
            'password.required' => 'The :attribute field is required',
        ];
    }
}
