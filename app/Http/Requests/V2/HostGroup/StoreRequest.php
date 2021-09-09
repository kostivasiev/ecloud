<?php

namespace App\Http\Requests\V2\HostGroup;

use App\Models\V2\Vpc;
use App\Rules\V2\ExistsForUser;
use App\Rules\V2\IsResourceAvailable;
use App\Rules\V2\Region\DoVpcAndAzRegionsMatch;
use UKFast\FormRequests\FormRequest;

class StoreRequest extends FormRequest
{
    public function rules()
    {
        return [
            'name' => 'nullable|string|max:255',
            'vpc_id' => [
                'required',
                'string',
                'exists:ecloud.vpcs,id,deleted_at,NULL',
                new ExistsForUser(Vpc::class),
                new IsResourceAvailable(Vpc::class),
                new DoVpcAndAzRegionsMatch('availability_zone_id'),
            ],
            'availability_zone_id' => [
                'required',
                'string',
                'exists:ecloud.availability_zones,id,deleted_at,NULL',
                new DoVpcAndAzRegionsMatch('vpc_id'),
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
