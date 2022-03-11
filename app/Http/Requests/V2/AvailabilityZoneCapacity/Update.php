<?php

namespace App\Http\Requests\V2\AvailabilityZoneCapacity;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class Update extends FormRequest
{
    /**
     * @return array
     */
    public function rules()
    {
        return [
            'availability_zone_id' => [
                'sometimes',
                'required',
                'string',
                'exists:ecloud.availability_zones,id,deleted_at,NULL',
            ],
            'type' => [
                'sometimes',
                'required',
                'string',
                Rule::unique('ecloud.availability_zone_capacities')->where(function ($query) {
                    return $query->where('availability_zone_id', app('request')->get('availability_zone_id'))->whereNull('deleted_at');
                })
            ],
            'alert_warning' => [
                'sometimes',
                'numeric',
                'nullable',
                'between:1,100'
            ],
            'alert_critical' => [
                'sometimes',
                'numeric',
                'nullable',
                'between:1,100'
            ],
            'max' => [
                'required',
                'numeric',
                'between:1,100'
            ]
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
            'protocol.in' => 'The :attribute field must contain one of TCP, UDP or ICMPv4',
        ];
    }
}
