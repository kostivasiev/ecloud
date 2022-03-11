<?php

namespace App\Http\Requests\V2\Software;

use App\Models\V2\Software;
use Illuminate\Validation\Rule;
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
            ],
            'platform' => [
                'sometimes',
                'required',
                'string',
                Rule::in([Software::PLATFORM_LINUX, Software::PLATFORM_WINDOWS])
            ],
            'visibility' => [
                'sometimes',
                'required',
                'string',
                Rule::in([Software::VISIBILITY_PUBLIC, Software::VISIBILITY_PRIVATE]),
            ],
            'license' => [
                'sometimes',
                'required',
                'string',
            ],
        ];
    }
}
