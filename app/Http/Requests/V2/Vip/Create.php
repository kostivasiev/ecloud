<?php

namespace App\Http\Requests\V2\Vip;

use UKFast\FormRequests\FormRequest;

class Create extends FormRequest
{
    /**
     * @return array
     */
    public function rules()
    {
        return [
            'ip_address_id' => [
                'required',
                'string',
                'exists:ecloud.ip_addresses,id,deleted_at,NULL',
            ],
            'network_id' => [
                'string'
            ],
            'allocate_floating_ip' => [
                'boolean'
            ],
        ];
    }

    /**
     * @return array
     */
    public function messages()
    {
        return [
            'required' => 'The :attribute field is required',
            'exists' => 'The specified :attribute was not found',
        ];
    }
}
