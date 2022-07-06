<?php

namespace App\Http\Requests\V2\Instance;

use App\Models\V2\HostGroup;
use App\Models\V2\Instance;
use App\Rules\V2\ExistsForUser;
use App\Rules\V2\HostGroup\HostGroupCanProvision;
use App\Rules\V2\Instance\IsCompatiblePlatform;
use App\Rules\V2\IsResourceAvailable;
use Illuminate\Foundation\Http\FormRequest;
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
                new HostGroupCanProvision($instance->ram_capacity),
            ]
        ];
    }
}
