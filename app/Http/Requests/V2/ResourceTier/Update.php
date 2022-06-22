<?php

namespace App\Http\Requests\V2\ResourceTier;

use Illuminate\Foundation\Http\FormRequest;

class Update extends FormRequest
{
    public function rules()
    {
        return [
            'name' => 'sometimes|string|max:255',
        ];
    }
}