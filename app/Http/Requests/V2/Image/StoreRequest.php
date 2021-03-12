<?php

namespace App\Http\Requests\V2\Image;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Vpc;
use App\Rules\V2\ExistsForUser;
use UKFast\FormRequests\FormRequest;

class StoreRequest extends FormRequest
{
    public function rules()
    {
        return [
            'appliance_version_id' => [
                'required',
                'string',
                'exists:ecloud.appliance_version,appliance_version_uuid,deleted_at,NULL',
            ],
        ];
    }
}
