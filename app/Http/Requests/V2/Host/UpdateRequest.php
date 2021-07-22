<?php

namespace App\Http\Requests\V2\Host;

use UKFast\FormRequests\FormRequest;

class UpdateRequest extends FormRequest
{
    public function rules()
    {

        return [
            'name' => 'sometimes|nullable|string|max:255',
        ];
    }
}
