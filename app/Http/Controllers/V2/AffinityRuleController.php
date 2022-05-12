<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\AffinityRule\Create;
use App\Http\Requests\V2\AffinityRule\Update;
use App\Models\V2\AffinityRule;
use App\Resources\V2\AffinityRuleResource;
use Illuminate\Http\Request;

class AffinityRuleController extends BaseController
{
    public function index(Request $request)
    {
        $collection = AffinityRule::forUser($request->user());

        return AffinityRuleResource::collection(
            $collection->search()
                ->paginate(
                    $request->input('per_page', env('PAGINATION_LIMIT'))
                )
        );
    }

    public function show(Request $request, string $affinityRuleId)
    {
        return new AffinityRuleResource(
            AffinityRule::forUser($request->user())->findOrFail($affinityRuleId)
        );
    }

    public function store(Create $request)
    {
        $model = app()->make(AffinityRule::class);
        $model->fill($request->only([
            'name',
            'vpc_id',
            'availability_zone_id',
            'type'
        ]));

        $task = $model->syncSave();
        return $this->responseIdMeta($request, $model->id, 202, $task->id);
    }

    public function update(Update $request, string $affinityRuleId)
    {
        $model = AffinityRule::forUser($request->user())->findOrFail($affinityRuleId);
        $model->update($request->only([
            'name',
            'type',
        ]));

        $task = $model->syncSave();
        return $this->responseIdMeta($request, $model->id, 202, $task->id);
    }

    public function destroy(Request $request, string $affinityRuleId)
    {
        $model = AffinityRule::forUser($request->user())->findOrFail($affinityRuleId);

        if ($model->members()->count() > 0) {
            //can not delete rule with members
            abort(500);
        }

        $task = $model->syncDelete();
        return $this->responseTaskId($task->id, 204);
    }
}
