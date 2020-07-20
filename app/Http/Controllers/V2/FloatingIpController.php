<?php

namespace App\Http\Controllers\V2;

use App\Events\V2\FloatingIps\AfterCreateEvent;
use App\Events\V2\FloatingIps\AfterDeleteEvent;
use App\Events\V2\FloatingIps\AfterUpdateEvent;
use App\Events\V2\FloatingIps\BeforeCreateEvent;
use App\Events\V2\FloatingIps\BeforeDeleteEvent;
use App\Events\V2\FloatingIps\BeforeUpdateEvent;
use App\Http\Requests\V2\CreateFloatingIpRequest;
use App\Http\Requests\V2\UpdateFloatingIpRequest;
use App\Models\V2\FloatingIp;
use App\Resources\V2\FloatingIpResource;
use Illuminate\Http\Request;
use UKFast\DB\Ditto\QueryTransformer;

/**
 * Class InstanceController
 * @package App\Http\Controllers\V2
 */
class FloatingIpController extends BaseController
{
    /**
     * Get resource collection
     * @param \Illuminate\Http\Request $request
     * @param QueryTransformer $queryTransformer
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, QueryTransformer $queryTransformer)
    {
        $collection = FloatingIp::query();

        $queryTransformer->config(FloatingIp::class)
            ->transform($collection);

        return FloatingIpResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param string $instanceId
     * @return FloatingIpResource
     */
    public function show(Request $request, string $instanceId)
    {
        return new FloatingIpResource(
            FloatingIp::findOrFail($instanceId)
        );
    }

    /**
     * @param CreateFloatingIpRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(CreateFloatingIpRequest $request)
    {
        event(new BeforeCreateEvent());
        $resource = new FloatingIp(
            //$request->only([''])
        );
        $resource->save();
        $resource->refresh();
        event(new AfterCreateEvent());
        return $this->responseIdMeta($request, $resource->getKey(), 201);
    }

    /**
     * @param UpdateFloatingIpRequest $request
     * @param string $instanceId
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateFloatingIpRequest $request, string $instanceId)
    {
        event(new BeforeUpdateEvent());
        $resource = FloatingIp::findOrFail($instanceId);
        //$instance->fill($request->only([]));
        $resource->save();
        event(new AfterUpdateEvent());
        return $this->responseIdMeta($request, $resource->getKey(), 200);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param string $instanceId
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, string $instanceId)
    {
        event(new BeforeDeleteEvent());
        $resource = FloatingIp::findOrFail($instanceId);
        $resource->delete();
        event(new AfterDeleteEvent());
        return response()->json([], 204);
    }
}
