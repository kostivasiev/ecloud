<?php

namespace App\Http\Requests\V2\HostSpec;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class Create extends FormRequest
{
    public function rules()
    {
        $rules = [
            'name' => [
                'nullable',
                'string',
                'max:255'
            ],
            'ucs_specification_name' => [
                'required',
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
            ],
            'is_hidden' => [
                'sometimes',
                'boolean',
            ]
        ];

        return $rules;
    }
}
