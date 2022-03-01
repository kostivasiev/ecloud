<?php

namespace App\Http\Requests\V2;

use Illuminate\Support\Facades\Auth;
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
        $rules = [
            'name' => ['nullable', 'string', 'max:255'],
            'resource_id' => ['required', 'string'],
            'host' => ['nullable', 'string'],
            'username' => ['nullable', 'string'],
            'password' => ['nullable', 'string'],
            'port' => ['nullable', 'integer'],
        ];
        if (Auth::user()->isAdmin()) {
            $rules['is_hidden'] = ['sometimes', 'boolean'];
        }
        return $rules;
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
