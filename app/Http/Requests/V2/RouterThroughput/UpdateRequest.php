<?php

namespace App\Http\Requests\V2\RouterThroughput;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255'
            ],
            'availability_zone_id' => [
                'sometimes',
                'required',
                'string',
                'exists:ecloud.availability_zones,id,deleted_at,NULL',
            ],
            'committed_bandwidth' => [
                'sometimes',
                'required',
                'integer'
            ],
        ];
    }

    /**
     * Get the validation messages that apply to the request.
     *
     * @return array|string[]
     */
    public function messages()
    {
        return [
            'required' => 'The :attribute field is required',
        ];
    }
}
