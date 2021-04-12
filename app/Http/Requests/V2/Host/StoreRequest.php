<?php

namespace App\Http\Requests\V2\Host;

use UKFast\FormRequests\FormRequest;

class StoreRequest extends FormRequest
{
    public function rules()
    {
        return [
            'name' => 'nullable|string|max:50',
            'host_group_id' => [
                'required',
                'string',
                'exists:ecloud.host_groups,id,deleted_at,NULL',
            ],
        ];
    }
}
