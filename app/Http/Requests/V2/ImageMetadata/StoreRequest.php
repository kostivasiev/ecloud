<?php

namespace App\Http\Requests\V2\ImageMetadata;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    public function rules()
    {
        return [
            'image_id' => [
                'required',
                'exists:ecloud.images,id,deleted_at,NULL',
            ],
            'key' => [
                'required',
                'string'
            ],
            'value' => [
                'required',
                'string',
            ],
        ];
    }
}
