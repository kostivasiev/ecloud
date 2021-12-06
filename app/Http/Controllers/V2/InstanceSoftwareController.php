<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\InstanceSoftware\Create;
use App\Http\Requests\V2\InstanceSoftware\Update;
use App\Models\V2\InstanceSoftware;
use App\Resources\V2\InstanceSoftwareResource;
use Illuminate\Http\Request;
use UKFast\DB\Ditto\QueryTransformer;

class InstanceSoftwareController extends BaseController
{
    public function index(Request $request, QueryTransformer $queryTransformer)
    {
        $collection = InstanceSoftware::forUser($request->user());

        $queryTransformer->config(InstanceSoftware::class)
            ->transform($collection);

        return InstanceSoftwareResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    public function show(Request $request, string $instanceSoftwareId)
    {
        return new InstanceSoftwareResource(
            InstanceSoftware::forUser($request->user())->findOrFail($instanceSoftwareId)
        );
    }

    public function store(Create $request)
    {
        $resource = new InstanceSoftware($request->only([
            'name',
            'instance_id',
            'software_id',
        ]));
        $task = $resource->syncSave();
        return $this->responseIdMeta($request, $resource->id, 202, $task->id);
    }

    public function update(Update $request, string $instanceSoftwareId)
    {
        $resource = InstanceSoftware::forUser($request->user())->findOrFail($instanceSoftwareId);
        $resource->fill($request->only([
            'name',
        ]));
        $task = $resource->syncSave();
        return $this->responseIdMeta($request, $resource->id, 202, $task->id);
    }

    public function destroy(Request $request, string $instanceSoftwareId)
    {
        $resource = InstanceSoftware::forUser($request->user())->findOrFail($instanceSoftwareId);

        $task = $resource->syncDelete();
        return $this->responseTaskId($task->id);
    }
}
