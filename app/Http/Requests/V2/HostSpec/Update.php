<?php

namespace App\Http\Requests\V2\HostSpec;

use Illuminate\Foundation\Http\FormRequest;

class Update extends FormRequest
{
    public function rules()
    {
        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255'
            ],
            'ucs_specification_name' => [
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
}
