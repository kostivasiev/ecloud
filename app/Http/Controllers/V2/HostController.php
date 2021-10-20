<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\Host\StoreRequest;
use App\Http\Requests\V2\Host\UpdateRequest;
use App\Models\V2\Host;
use App\Models\V2\Task;
use App\Resources\V2\HostResource;
use App\Resources\V2\TaskResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use UKFast\DB\Ditto\QueryTransformer;

class HostController extends BaseController
{
    public function index(Request $request, QueryTransformer $queryTransformer)
    {
        $collection = Host::forUser($request->user());

        $queryTransformer->config(Host::class)
            ->transform($collection);

        return HostResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    public function show(Request $request, string $id)
    {
        return new HostResource(
            Host::forUser($request->user())->findOrFail($id)
        );
    }

    public function store(StoreRequest $request)
    {
        $model = app()->make(Host::class);
        $model->fill($request->only([
            'name',
            'host_group_id',
        ]));

        $task = $model->syncSave();
        return $this->responseIdMeta($request, $model->id, 202, $task->id);
    }

    public function update(UpdateRequest $request, string $id)
    {
        $model = Host::forUser(Auth::user())->findOrFail($id);
        $model->fill($request->only([
            'name',
        ]));

        $task = $model->syncSave();
        return $this->responseIdMeta($request, $model->id, 202, $task->id);
    }

    public function destroy(Request $request, string $id)
    {
        $model = Host::forUser($request->user())->findOrFail($id);
        $hostGroup = $model->hostGroup;
        $hostSpec = $hostGroup->hostSpec;

        if ($hostGroup->hosts()->count() == 1 && $hostGroup->instances()->count() > 0) {
            return response()->json([
                'title' => 'Validation Error',
                'detail' => 'Can not delete Host with active instances',
                'status' => 422,
            ], 422);
        }

        if ($hostGroup->ram_used > (($hostGroup->hosts->count() * $hostSpec->ram_capacity) - $hostSpec->ram_capacity)) {
            return response()->json([
                'title' => 'Validation Error',
                'detail' => 'Host removal will result in insufficient ram capacity for existing instances',
                'status' => 422,
            ], 422);
        }

        if ($hostGroup->vcpu_used > ($hostGroup->vcpu_capacity - ($hostSpec->cpu_cores * $hostSpec->cpu_sockets))) {
            return response()->json([
                'title' => 'Validation Error',
                'detail' => 'Host removal will result in insufficient vcpu capacity for existing instances',
                'status' => 422,
            ], 422);
        }

        $task = $model->syncDelete();
        return $this->responseTaskId($task->id);
    }

    public function tasks(Request $request, QueryTransformer $queryTransformer, string $id)
    {
        $collection = Host::forUser($request->user())->findOrFail($id)->tasks();
        $queryTransformer->config(Task::class)
            ->transform($collection);

        return TaskResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }
}
