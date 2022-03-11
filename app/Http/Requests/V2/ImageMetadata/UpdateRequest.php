<?php

namespace App\Http\Requests\V2\ImageMetadata;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    public function rules()
    {
        return [
            'image_id' => [
                'sometimes',
                'required',
                'exists:ecloud.images,id,deleted_at,NULL',
            ],
            'key' => [
                'sometimes',
                'required',
                'string'
            ],
            'value' => [
                'sometimes',
                'required',
                'string',
            ],
        ];
    }
}
