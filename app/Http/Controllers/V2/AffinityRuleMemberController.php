<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\AffinityRuleMember\Create;
use App\Models\V2\AffinityRule;
use App\Models\V2\AffinityRuleMember;
use App\Resources\V2\AffinityRuleMemberResource;
use Illuminate\Http\Request;

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

    public function show(Request $request, string $affinityRuleMemberId)
    {
        $affinityRuleMember = AffinityRuleMember::forUser($request->user())
            ->findOrFail($affinityRuleMemberId);

        return new AffinityRuleMemberResource(
            $affinityRuleMember
        );
    }

    public function store(Create $request)
    {
        $model = app()->make(AffinityRuleMember::class);
        $instanceId = $request->instance_id;
        $affinityRuleId = $request->affinity_rule_id;

        $model->fill([
            'instance_id' => $instanceId,
            'affinity_rule_id' => $affinityRuleId
        ]);

        $task = $model->affinityRule
            ->withTaskLock(function () use ($model) {
                return $model->syncSave();
            });

        return $this->responseIdMeta($request, $model->id, 202, $task->id);
    }

    public function destroy(Request $request, string $affinityRuleMemberId)
    {
        $member = AffinityRuleMember::forUser($request->user())
            ->findOrFail($affinityRuleMemberId);
        $task = $member->syncDelete();
        return $this->responseTaskId($task->id);
    }
}
