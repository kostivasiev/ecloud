<?php

namespace App\Http\Requests\V2\HostGroup;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Vpc;
use App\Rules\V2\ExistsForUser;
use App\Rules\V2\IsResourceAvailable;
use UKFast\FormRequests\FormRequest;

class StoreRequest extends FormRequest
{
    public function rules()
    {
        return [
            'name' => 'nullable|string|max:50',
            'vpc_id' => [
                'required',
                'string',
                'exists:ecloud.vpcs,id,deleted_at,NULL',
                new ExistsForUser(Vpc::class),
                new IsResourceAvailable(Vpc::class),
            ],
            'availability_zone_id' => [
                'sometimes',
                'required',
                'string',
                'exists:ecloud.availability_zones,id,deleted_at,NULL',
                //new ExistsForUser(AvailabilityZone::class) // Commented out so that we can UAT into G0
            ],
            'host_spec_id' => [
                'required',
                'string',
                'exists:ecloud.host_specs,id,deleted_at,NULL',
            ],
            'windows_enabled' => [
                'sometimes',
                'required',
                'boolean'
            ]
        ];
    }
}
