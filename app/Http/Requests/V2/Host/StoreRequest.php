<?php

namespace App\Http\Requests\V2\Host;

use App\Models\V2\HostGroup;
use App\Rules\V2\IsResourceAvailable;
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
                new IsResourceAvailable(HostGroup::class),
            ],
        ];
    }
}
