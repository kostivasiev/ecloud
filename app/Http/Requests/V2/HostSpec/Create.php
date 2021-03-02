<?php

namespace App\Http\Requests\V2\HostSpec;

use UKFast\FormRequests\FormRequest;

class Create extends FormRequest
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
     * @return array
     */
    public function rules()
    {
        return [
            'name' => [
                'nullable',
                'string'
            ],
            'cpu_sockets' => [
                'required',
                'integer'
            ],
            'cpu_type' => [
                'required',
                'string'
            ],
            'cpu_cores' => [
                'required',
                'integer'
            ],
            'cpu_clock_speed' => [
                'required',
                'integer'
            ],
            'ram_capacity' => [
                'required',
                'integer'
            ],
            'availability_zones' => [
                'sometimes',
                'required',
                'array'
            ],
            'availability_zones.*.id' => [
                'required',
                'string',
                'exists:ecloud.availability_zones,id,deleted_at,NULL',
            ]
        ];
    }

    /**
     * @return array
     */
    public function messages()
    {
        return [];
    }
}
