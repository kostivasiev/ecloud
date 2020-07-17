<?php

namespace App\Http\Controllers\V2;

use App\Events\V2\Instances\AfterCreateEvent;
use App\Events\V2\Instances\AfterDeleteEvent;
use App\Events\V2\Instances\AfterUpdateEvent;
use App\Events\V2\Instances\BeforeCreateEvent;
use App\Events\V2\Instances\BeforeDeleteEvent;
use App\Events\V2\Instances\BeforeUpdateEvent;
use App\Http\Requests\V2\CreateInstanceRequest;
use App\Http\Requests\V2\UpdateInstanceRequest;
use App\Models\V2\Instance;
use App\Resources\V2\InstanceResource;
use Illuminate\Http\Request;
use UKFast\DB\Ditto\QueryTransformer;

/**
 * Class InstanceController
 * @package App\Http\Controllers\V2
 */
class InstanceController extends BaseController
{
    /**
     * Get instance collection
     * @param \Illuminate\Http\Request $request
     * @param QueryTransformer $queryTransformer
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, QueryTransformer $queryTransformer)
    {
        $collection = Instance::query();

        $queryTransformer->config(Instance::class)
            ->transform($collection);

        return InstanceResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param string $instanceId
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, string $instanceId)
    {
        return new InstanceResource(
            Instance::findOrFail($instanceId)
        );
    }

    /**
     * @param \App\Http\Requests\V2\CreateRoutersRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(CreateInstanceRequest $request)
    {
        event(new BeforeCreateEvent());
        $instance = new Instance($request->only(['network_id']));
        $instance->save();
        $instance->refresh();
        event(new AfterCreateEvent());
        return $this->responseIdMeta($request, $instance->getKey(), 201);
    }

    /**
     * @param UpdateInstanceRequest $request
     * @param string $instanceId
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateInstanceRequest $request, string $instanceId)
    {
        event(new BeforeUpdateEvent());
        $instance = Instance::findOrFail($instanceId);
        $instance->fill($request->only(['network_id']));
        $instance->save();
        event(new AfterUpdateEvent());
        return $this->responseIdMeta($request, $instance->getKey(), 200);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param string $instanceId
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, string $instanceId)
    {
        event(new BeforeDeleteEvent());
        $instance = Instance::findOrFail($instanceId);
        $instance->delete();
        event(new AfterDeleteEvent());
        return response()->json([], 204);
    }
}
