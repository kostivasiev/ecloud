<?php

namespace App\Http\Requests\V2\InstanceSoftware;

use Illuminate\Foundation\Http\FormRequest;

class Update extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'name' => [
                'sometimes',
                'required',
                'string'
            ]
        ];
    }
}
