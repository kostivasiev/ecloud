<?php

namespace App\Http\Requests\V2\AffinityRuleMember;

use App\Models\V2\AffinityRule;
use App\Models\V2\Instance;
use App\Models\V2\Router;
use App\Rules\V2\ExistsForUser;
use App\Rules\V2\IsResourceAvailable;
use App\Rules\V2\IsSameAvailabilityZone;
use App\Rules\V2\IsSameVpc;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Validation\Rule;

/**
 * @property $instance_id
 * @property $affinity_rule_id
 */
class Create extends FormRequest
{
    public function rules()
    {
        $availabilityZoneId = '';
        $vpcId = '';
        $affinityRule = AffinityRule::forUser(Auth::user())->find(Request::input('affinity_rule_id'));
        if (!empty($affinityRule)) {
            /** @var Instance $affinityRule */
            $availabilityZoneId = $affinityRule->availability_zone_id;
            $vpcId = $affinityRule->vpc_id;
        }

        return [
            'instance_id' => [
                'required',
                'string',
                'exists:ecloud.instances,id,deleted_at,NULL',
                Rule::unique('ecloud.affinity_rule_members', 'instance_id')
                    ->where('deleted_at', null),
                new IsSameVpc($vpcId),
                new ExistsForUser(Instance::class),
                new IsSameAvailabilityZone($availabilityZoneId),
                new IsResourceAvailable(Instance::class),
            ],
            'affinity_rule_id' => [
                'required',
                'string',
                'exists:ecloud.affinity_rules,id,deleted_at,NULL',
                new ExistsForUser(AffinityRule::class),
                new IsResourceAvailable(AffinityRule::class),
            ],
        ];
    }
}
