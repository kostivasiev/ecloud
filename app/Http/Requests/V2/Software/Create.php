<?php

namespace App\Http\Requests\V2\Software;

use App\Models\V2\Software;
use Illuminate\Validation\Rule;
use UKFast\FormRequests\FormRequest;

class Create extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'sometimes',
                'required',
                'string'
            ],
            'platform' => [
                'required',
                'string',
                Rule::in([Software::PLATFORM_LINUX, Software::PLATFORM_WINDOWS])
            ],
            'visibility' => [
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
