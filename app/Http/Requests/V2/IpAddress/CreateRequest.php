<?php

namespace App\Http\Requests\V2\IpAddress;

use App\Models\V2\IpAddress;
use Illuminate\Validation\Rule;
use UKFast\FormRequests\FormRequest;

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
            'ip_address' => [
                'required',
                'ip',
                'unique:ecloud.ip_addresses,ip_address,NULL,id,deleted_at,NULL'
            ],
            'type' => [
                'required',
                'string',
                Rule::in([IpAddress::TYPE_NORMAL,IpAddress::TYPE_CLUSTER])
            ]
        ];
    }
}
