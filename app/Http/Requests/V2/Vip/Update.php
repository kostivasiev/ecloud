<?php

namespace App\Http\Requests\V2\Vip;

use App\Models\V2\IpAddress;
use Illuminate\Validation\Rule;
use UKFast\FormRequests\FormRequest;

class Update extends FormRequest
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
                Rule::exists(IpAddress::class, 'id')->whereNull('deleted_at')
            ],
            'network_id' => [
                'string'
            ],
            'allocate_floating_ip' => [
                'numeric'
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
