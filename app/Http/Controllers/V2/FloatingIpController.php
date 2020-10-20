<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\FloatingIp\CreateRequest;
use App\Http\Requests\V2\FloatingIp\UpdateRequest;
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
     * @param CreateRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(CreateRequest $request)
    {
        $resource = new FloatingIp(
            $request->only(['vpc_id', 'name'])
        );
        $resource->save();
        $resource->refresh();
        return $this->responseIdMeta($request, $resource->getKey(), 201);
    }

    /**
     * @param UpdateRequest $request
     * @param string $instanceId
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateRequest $request, string $fipId)
    {
        $resource = FloatingIp::forUser(app('request')->user)->findOrFail($fipId);
        $resource->fill($request->only(['name']));
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
