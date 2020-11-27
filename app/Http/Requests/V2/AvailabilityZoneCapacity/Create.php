<?php

namespace App\Http\Requests\V2\AvailabilityZoneCapacity;

use Illuminate\Validation\Rule;
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
            'availability_zone_id' => [
                'required',
                'string',
                'exists:ecloud.availability_zones,id,deleted_at,NULL',
            ],
            'type' => [
                'required',
                'string',
                Rule::unique('ecloud.availability_zone_capacities')->where(function ($query) {
                    return $query->where('availability_zone_id', app('request')->get('availability_zone_id'));
                })
            ],
            'alert_warning' => [
                'sometimes',
                'numeric',
                'between:1,100'
            ],
            'alert_critical' => [
                'sometimes',
                'numeric',
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
            'unique' => 'The :attribute field must be unique per availability zone',
            'required' => 'The :attribute field is required',
        ];
    }
}
