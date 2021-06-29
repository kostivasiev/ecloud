<?php

namespace App\Http\Requests\V2\VpnProfile;

use UKFast\FormRequests\FormRequest;

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
            'ike_version' => [
                'sometimes',
                'required',
                'string',
                'in:ike_v1,ike_v2,ike_flex',
            ],
            'encryption_algorithm' => 'sometimes|required|array|min:1',
            'encryption_algorithm.*' => [
                'sometimes',
                'required',
                'string',
                'in:aes 128,aes 256,aes gcm 128,aes gcm 192,aes gcm 256',
            ],
            'digest_algorithm' => 'sometimes|required|array|min:1',
            'digest_algorithm.*' => [
                'sometimes',
                'required',
                'string',
                'in:sha1,sha2 256,sha2 384,sha2 512',
            ],
            'diffie_-_hellman' => 'sometimes|required|array|min:1',
            'diffie_-_hellman.*' => [
                'sometimes',
                'required',
                'string',
                'in:group 2,group 5,group 14,group 15,group 16,group 19,group 20,group 21',
            ],
        ];
    }
}
