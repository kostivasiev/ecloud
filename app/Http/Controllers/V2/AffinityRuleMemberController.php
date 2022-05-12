<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\AffinityRuleMember\Create;
use App\Models\V2\AffinityRule;
use App\Models\V2\AffinityRuleMember;
use App\Resources\V2\AffinityRuleMemberResource;
use App\Rules\V2\ExistsForUser;
use App\Rules\V2\IsResourceAvailable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use UKFast\Api\Exceptions\BadRequestException;

class AffinityRuleMemberController extends BaseController
{
    public function index(Request $request, string $affinityRuleId)
    {
        $affinityRule = AffinityRule::forUser($request->user())->findOrFail($affinityRuleId);

        $collection = $affinityRule->affinityRuleMembers();

        return AffinityRuleMemberResource::collection(
            $collection->search()
                ->paginate(
                    $request->input('per_page', env('PAGINATION_LIMIT'))
                )
        );
    }

    public function show(Request $request, string $affinityRuleId, string $affinityRuleMemberId)
    {
        $affinityRule = AffinityRule::forUser($request->user())
            ->findOrFail($affinityRuleId);

        return new AffinityRuleMemberResource(
            $affinityRule->affinityRuleMembers()
                ->findOrFail($affinityRuleMemberId)
        );
    }

    public function store(Create $request, $affinityRuleId)
    {
        $model = app()->make(AffinityRuleMember::class);
        $instanceId = $request->instance_id;

        $model->fill([
            'instance_id' => $instanceId,
            'rule_id' => $affinityRuleId
        ]);

        $task = $model->syncSave();

        return $this->responseIdMeta($request, $model->id, 202, $task->id);
    }

    public function destroy(Request $request, string $affinityRuleId, string $affinityRuleMemberId)
    {
        AffinityRule::forUser($request->user())
            ->findOrFail($affinityRuleId);

        $member = AffinityRuleMember::forUser($request->user())
            ->findOrFail($affinityRuleMemberId);

        $task = $member->syncDelete();
        return $this->responseTaskId($task->id, 204);
    }
}
