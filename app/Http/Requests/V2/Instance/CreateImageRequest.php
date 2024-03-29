<?php

namespace App\Http\Requests\V2\Instance;

use Illuminate\Foundation\Http\FormRequest;

class CreateImageRequest extends FormRequest
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
                'string'
            ],
            'description' => [
                'sometimes',
                'nullable',
                'string'
            ],
        ];
    }
}
