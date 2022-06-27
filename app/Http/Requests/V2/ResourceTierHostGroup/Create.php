<?php

namespace App\Http\Requests\V2\ResourceTierHostGroup;

use App\Models\V2\HostGroup;
use App\Models\V2\ResourceTier;
use App\Rules\V2\IsSameAvailabilityZone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Request;
use Illuminate\Validation\Rule;

class Create extends FormRequest
{
    public function rules()
    {
        return [
            'resource_tier_id' => [
                'required',
                'string',
                Rule::exists(ResourceTier::class, 'id')->whereNull('deleted_at'),
            ],
            'host_group_id' => [
                'required',
                'string',
                Rule::exists(HostGroup::class, 'id')->whereNull('vpc_id')->whereNull('deleted_at'),
                new IsSameAvailabilityZone(Request::input('resource_tier_id'))
            ]
        ];
    }

}