<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\AffinityRule\Update;
use App\Http\Requests\V2\AffinityRuleMember\Create;
use App\Models\V2\AffinityRule;
use App\Models\V2\AffinityRuleMember;
use App\Resources\V2\AffinityRuleMemberResource;
use Illuminate\Http\Request;

class AffinityRuleMemberController extends BaseController
{
    public function index(Request $request, string $affinityRuleId)
    {
        $rule = AffinityRule::forUser($request->user())->findOrFail($affinityRuleId);

        $collection = $rule->members();

        return AffinityRuleMemberResource::collection(
            $collection->search()
                ->paginate(
                    $request->input('per_page', env('PAGINATION_LIMIT'))
                )
        );
    }

    public function show(Request $request, string $affinityRuleId, string $affinityRuleMemberId)
    {
        $rule = AffinityRule::forUser($request->user())
            ->findOrFail($affinityRuleId);

        return new AffinityRuleMemberResource(
            $rule->members()
                ->findOrFail($affinityRuleMemberId)
        );
    }

    public function store(Create $request)
    {
        $model = app()->make(AffinityRuleMember::class);
        $model->fill($request->only([
            'rule_id',
            'instance_id',
        ]));

        $task = $model->syncSave();

        return $this->responseIdMeta($request, $model->id, 202, $task->id);
    }

    public function update(Update $request, string $affinityRuleId, string $affinityRuleMemberId)
    {
        //Check User has access to the rule.
        AffinityRule::forUser($request->user())
            ->findOrFail($affinityRuleId);

        //Update the specific member;
        $model = AffinityRuleMember::forUser($request->user())
            ->findOrFail($affinityRuleMemberId);

        $model->update($request->only([
            'rule_id',
            'instance_id',
        ]));

        $task = $model->syncSave();

        return $this->responseIdMeta($request, $model->id, 202, $task->id);
    }

    public function destroy(Request $request, string $affinityRuleId, string $affinityRuleMemberId)
    {
        AffinityRule::forUser($request->user())
            ->findOrFail($affinityRuleId);

        $model = AffinityRuleMember::forUser($request->user())
            ->findOrFail($affinityRuleMemberId);

        $task = $model->syncDelete();
        return $this->responseTaskId($task->id, 204);
    }
}
