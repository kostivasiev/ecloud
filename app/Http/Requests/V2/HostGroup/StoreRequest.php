<?php

namespace App\Http\Requests\V2\HostGroup;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\HostSpec;
use App\Models\V2\Vpc;
use App\Rules\V2\ExistsForUser;
use App\Rules\V2\IsResourceAvailable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreRequest extends FormRequest
{
    public function rules()
    {
        $rules = [
            'name' => 'nullable|string|max:255',
            'vpc_id' => [
                'required',
                'string',
                'exists:ecloud.vpcs,id,deleted_at,NULL',
                new ExistsForUser(Vpc::class),
                new IsResourceAvailable(Vpc::class),
            ],
            'availability_zone_id' => [
                'required',
                'string',
                'exists:ecloud.availability_zones,id,deleted_at,NULL',
                new ExistsForUser(AvailabilityZone::class),
            ],
            'host_spec_id' => [
                'required',
                'string',
                'exists:ecloud.host_specs,id,deleted_at,NULL',
                new ExistsForUser(HostSpec::class),
            ],
            'windows_enabled' => [
                'sometimes',
                'required',
                'boolean'
            ]
        ];

        if (Auth::user()->isAdmin()) {
            $rules['is_hidden'] = [
                'sometimes',
                'required',
                'boolean'
            ];
        }

        return $rules;
    }
}
