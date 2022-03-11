<?php

namespace App\Http\Requests\V2\SshKeyPair;

use App\Rules\V2\IsValidSshPublicKey;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class UpdateVirtualPrivateCloudsRequest
 * @package App\Http\Requests\V2\Vpc
 */
class UpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'sometimes|required|string|max:255',
            'public_key' => [
                'sometimes',
                'required',
                'string',
                new IsValidSshPublicKey(),
            ]
        ];
    }
}
