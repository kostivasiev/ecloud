<?php

namespace App\Http\Requests\V2\HostSpec;

use UKFast\FormRequests\FormRequest;

class Update extends FormRequest
{
    /**
     * @return array
     */
    public function rules()
    {
        return [
            'name' => [
                'sometimes',
                'required',
                'string'
            ],
            'cpu_sockets' => [
                'sometimes',
                'required',
                'integer'
            ],
            'cpu_type' => [
                'sometimes',
                'required',
                'string'
            ],
            'cpu_cores' => [
                'sometimes',
                'required',
                'integer'
            ],
            'cpu_clock_speed' => [
                'sometimes',
                'required',
                'integer'
            ],
            'ram_capacity' => [
                'sometimes',
                'required',
                'integer'
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
