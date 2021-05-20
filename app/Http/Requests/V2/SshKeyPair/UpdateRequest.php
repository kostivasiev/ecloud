<?php

namespace App\Http\Requests\V2\SshKeyPair;

use App\Rules\V2\IsValidSshPublicKey;
use UKFast\FormRequests\FormRequest;

/**
 * Class UpdateVirtualPrivateCloudsRequest
 * @package App\Http\Requests\V2\Vpc
 */
class UpdateRequest extends FormRequest
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
            'name' => 'sometimes|required|string',
            'public_key' => [
                'sometimes',
                'required',
                'string',
                new IsValidSshPublicKey(),
            ]
        ];
    }
}
