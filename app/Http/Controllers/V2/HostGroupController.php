<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\HostGroup\StoreRequest;
use App\Http\Requests\V2\HostGroup\UpdateRequest;
use App\Models\V2\AvailabilityZone;
use App\Models\V2\HostGroup;
use App\Models\V2\Task;
use App\Models\V2\Vpc;
use App\Resources\V2\HostGroupResource;
use App\Resources\V2\TaskResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use UKFast\DB\Ditto\QueryTransformer;

class HostGroupController extends BaseController
{
    public function index(Request $request)
    {
        $collection = HostGroup::forUser($request->user());

        return HostGroupResource::collection(
            $collection->search()
                ->paginate(
                    $request->input('per_page', env('PAGINATION_LIMIT'))
                )
        );
    }

    public function show(Request $request, string $id)
    {
        return new HostGroupResource(
            HostGroup::forUser($request->user())->findOrFail($id)
        );
    }

    public function store(StoreRequest $request)
    {
        $availabilityZone = AvailabilityZone::forUser(Auth::user())
            ->findOrFail($request->availability_zone_id)
            ->region_id;
        $vpc = Vpc::forUser(Auth::user())->findOrFail($request->vpc_id)->region_id;

        if ($availabilityZone !== $vpc) {
            return response()->json([
                'errors' => [
                    'title' => 'Not Found',
                    'detail' => 'The specified availability zone is not available to that VPC',
                    'status' => 404,
                    'source' => 'availability_zone_id'
                ]
            ], 404);
        }

        $model = app()->make(HostGroup::class);
        $model->fill($request->only([
            'name',
            'vpc_id',
            'availability_zone_id',
            'host_spec_id',
            'windows_enabled',
        ]));
        $task = $model->syncSave();

        return $this->responseIdMeta($request, $model->id, 202, $task->id);
    }

    public function update(UpdateRequest $request, string $id)
    {
        $model = HostGroup::forUser(Auth::user())->findOrFail($id);
        $model->fill($request->only([
            'name',
        ]));

        $task = $model->syncSave();

        return $this->responseIdMeta($request, $model->id, 202, $task->id);
    }

    public function destroy(Request $request, string $id)
    {
        $model = HostGroup::forUser($request->user())->findOrFail($id);

        if ($model->hosts()->count()) {
            return response()->json([
                'title' => 'Validation Error',
                'detail' => 'Can not delete Host group with active hosts',
                'status' => 422,
            ], 422);
        }

        $task = $model->syncDelete();
        return $this->responseTaskId($task->id);
    }

    public function tasks(Request $request, QueryTransformer $queryTransformer, string $id)
    {
        $collection = HostGroup::forUser($request->user())->findOrFail($id)->tasks();
        $queryTransformer->config(Task::class)
            ->transform($collection);

        return TaskResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }
}
