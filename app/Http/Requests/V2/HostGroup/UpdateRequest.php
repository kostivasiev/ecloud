<?php

namespace App\Http\Requests\V2\HostGroup;

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
