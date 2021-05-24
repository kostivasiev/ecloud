<?php

namespace App\Http\Requests\V2\Instance;

use UKFast\FormRequests\FormRequest;

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
            'name' => 'required|string',
        ];
    }
}
