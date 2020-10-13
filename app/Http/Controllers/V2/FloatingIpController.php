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
        $collection = FloatingIp::forUser($request->user);

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
            FloatingIp::forUser($request->user)->findOrFail($instanceId)
        );
    }

    /**
     * @param CreateFloatingIpRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(CreateFloatingIpRequest $request)
    {
        $resource = new FloatingIp(
            $request->only(['vpc_id'])
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
    public function update(UpdateFloatingIpRequest $request, string $fipId)
    {
        $resource = FloatingIp::forUser(app('request')->user)->findOrFail($fipId);
        //$instance->fill($request->only([]));
        $resource->save();
        return $this->responseIdMeta($request, $resource->getKey(), 200);
    }

    /**
     * @param Request $request
     * @param string $instanceId
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, string $fipId)
    {
        $resource = FloatingIp::forUser(app('request')->user)->findOrFail($fipId);
        $resource->delete();
        return response()->json([], 204);
    }
}
