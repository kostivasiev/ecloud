<?php

namespace App\Http\Requests\V2\ImageParameter;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    public function rules()
    {
        return [
            'name' => [
                'required',
                'string'
            ],
            'image_id' => [
                'required',
                'exists:ecloud.images,id,deleted_at,NULL',
            ],
            'type' => [
                'required',
                'in:String,Numeric,Boolean,Password'
            ],
            'key' => [
                'required',
                'regex:/^\w*$/'
            ],
            'description' => [
                'nullable',
                'max:255'
            ],
            'required' => [
                'required',
                'boolean'
            ],
            'is_hidden' => [
                'sometimes',
                'required',
                'boolean'
            ],
        ];
    }
}
