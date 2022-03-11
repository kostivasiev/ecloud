<?php

namespace App\Http\Requests\V2;

use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCredentialRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'resource_id' => ['sometimes', 'nullable', 'string'],
            'host' => ['sometimes', 'nullable', 'string'],
            'username' => ['sometimes', 'nullable', 'string'],
            'password' => ['sometimes', 'nullable', 'string'],
            'port' => ['sometimes', 'nullable', 'integer'],
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
            'name.required' => 'The :attribute field, when specified, cannot be null',
            'username.required' => 'The :attribute field, when specified, cannot be null',
            'password.required' => 'The :attribute field, when specified, cannot be null',
        ];
    }
}
