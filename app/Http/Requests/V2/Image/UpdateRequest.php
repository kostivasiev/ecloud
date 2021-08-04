<?php

namespace App\Http\Requests\V2\Image;

use App\Models\V2\Image;
use UKFast\FormRequests\FormRequest;

class UpdateRequest extends FormRequest
{
    public function rules()
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255'
            ],
            'availability_zone_ids' => [
                'sometimes',
                'required',
                'array'
            ],
            'availability_zone_ids.*' => [
                'required',
                'string',
                'exists:ecloud.availability_zones,id,deleted_at,NULL',
            ],
            'logo_uri' => [
                'sometimes',
                'nullable',
                'max:255'
            ],
            'documentation_uri' => [
                'sometimes',
                'nullable'
            ],
            'description' => [
                'sometimes',
                'nullable'
            ],
            'script_template' => [
                'sometimes',
                'nullable'
            ],
            'vm_template' => [
                'sometimes',
                'nullable'
            ],
            'platform' => [
                'sometimes',
                'required'
            ],
            'active' => [
                'sometimes',
                'required',
                'boolean'
            ],
            'public' => [
                'sometimes',
                'required',
                'boolean'
            ],
            'visibility' => [
                'sometimes',
                'required',
                'string',
                'in:' . Image::VISIBILITY_PUBLIC . ','. Image::VISIBILITY_PRIVATE
            ],
        ];
    }
}
