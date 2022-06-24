<?php

namespace App\Http\Requests\V2\ResourceTier;

use Illuminate\Foundation\Http\FormRequest;

class Create extends FormRequest
{
    public function rules()
    {
        return [
            'name' => 'nullable|string|max:255',
            'availability_zone_id' => [
                'required',
                'string',
                'exists:ecloud.availability_zones,id,deleted_at,NULL',
            ],
        ];
    }
}
