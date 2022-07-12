<?php

namespace App\Http\Requests\V2\Instance;

use App\Models\V2\HostGroup;
use App\Models\V2\ResourceTier;
use App\Models\V2\Instance;
use App\Rules\V2\ExistsForUser;
use App\Rules\V2\HostGroup\HostGroupCanProvision;
use App\Rules\V2\Instance\IsCompatiblePlatform;
use App\Rules\V2\IsResourceAvailable;
use App\Rules\V2\IsSameAvailabilityZone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class MigrateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $instance = Instance::forUser(Auth::user())->findOrFail(Request::route('instanceId'));

        return [
            'host_group_id' => [
                'sometimes',
                'required',
                'string',
                'exists:ecloud.host_groups,id,deleted_at,NULL',
                new ExistsForUser(HostGroup::class),
                new IsResourceAvailable(HostGroup::class),
                new IsCompatiblePlatform,
                new IsSameAvailabilityZone(app('request')->route('instanceId')),
                new HostGroupCanProvision($instance->ram_capacity),
            ],
            'resource_tier_id' => [
                'sometimes',
                'required',
                Rule::exists(ResourceTier::class, 'id')->whereNull('deleted_at')->where('active', true),
                new IsSameAvailabilityZone(app('request')->route('instanceId')),
            ]
        ];
    }
}
