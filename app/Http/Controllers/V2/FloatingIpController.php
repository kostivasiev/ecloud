<?php

namespace App\Http\Controllers\V2;

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
     * @param Request $request
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
     * @param Request $request
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
        $resource = new FloatingIp(
        //$request->only([''])
        );
        $resource->save();
        $resource->refresh();
        return $this->responseIdMeta($request, $resource->getKey(), 201);
    }

    /**
     * @param UpdateFloatingIpRequest $request
     * @param string $instanceId
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateFloatingIpRequest $request, string $instanceId)
    {
        $resource = FloatingIp::findOrFail($instanceId);
        //$instance->fill($request->only([]));
        $resource->save();
        return $this->responseIdMeta($request, $resource->getKey(), 200);
    }

    /**
     * @param Request $request
     * @param string $instanceId
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, string $instanceId)
    {
        $resource = FloatingIp::findOrFail($instanceId);
        $resource->delete();
        return response()->json([], 204);
    }
}
