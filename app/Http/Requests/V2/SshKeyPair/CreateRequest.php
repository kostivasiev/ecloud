<?php

namespace App\Http\Requests\V2\SshKeyPair;

use App\Rules\V2\IsValidSshPublicKey;
use Illuminate\Foundation\Http\FormRequest;

class CreateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'nullable|string|max:255',
            'public_key' => [
                'required',
                'string',
                new IsValidSshPublicKey(),
            ],
        ];
    }
}
