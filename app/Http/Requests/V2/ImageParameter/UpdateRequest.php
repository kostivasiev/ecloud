<?php

namespace App\Http\Requests\V2\ImageParameter;

use UKFast\FormRequests\FormRequest;

class UpdateRequest extends FormRequest
{
    public function rules()
    {
        return [
            'name' => [
                'sometimes',
                'required',
                'string'
            ],
            'image_id' => [
                'sometimes',
                'required',
                'exists:ecloud.images,id,deleted_at,NULL',
            ],
            'type' => [
                'sometimes',
                'required',
                'in:String,Numeric,Boolean,Password'
            ],
            'key' => [
                'sometimes',
                'required',
                'regex:/^\w*$/'
            ],
            'description' => [
                'sometimes',
                'nullable',
                'max:255'
            ],
            'required' => [
                'sometimes',
                'required',
                'boolean'
            ]
        ];
    }
}
