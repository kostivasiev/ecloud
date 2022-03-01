<?php

namespace App\Http\Requests\V2\SshKeyPair;

use App\Rules\V2\IsValidSshPublicKey;
use UKFast\FormRequests\FormRequest;

class CreateRequest extends FormRequest
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
            'name' => 'nullable|string|max:255',
            'public_key' => [
                'required',
                'string',
                new IsValidSshPublicKey(),
            ],
        ];
    }
}
