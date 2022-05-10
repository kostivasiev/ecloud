<?php

namespace App\Http\Requests\V2\AffinityRule;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class Update extends FormRequest
{
    public function rules()
    {
        return [
            'name' => 'sometimes|nullable|string|max:255',
            'type' => [
                'sometimes',
                'required',
                'string',
                Rule::in(['affinity', 'anti-affinity']),
            ],
        ];
    }
}
