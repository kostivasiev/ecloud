<?php

namespace App\Http\Requests\V2\AffinityRuleMember;

use App\Models\V2\AffinityRule;
use App\Models\V2\Instance;
use App\Rules\V2\ExistsForUser;
use App\Rules\V2\IsResourceAvailable;
use Illuminate\Foundation\Http\FormRequest;

/**
 * @property $instance_id
 */
class Create extends FormRequest
{
    public function rules()
    {
        return [
            'instance_id' => [
                'required',
                'string',
                'exists:ecloud.instances,id,deleted_at,NULL',
                new ExistsForUser(Instance::class),
                new IsResourceAvailable(Instance::class),
            ]
        ];
    }
}
