<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\FloatingIp\AssignRequest;
use App\Http\Requests\V2\FloatingIp\CreateRequest;
use App\Http\Requests\V2\FloatingIp\UpdateRequest;
use App\Jobs\FloatingIp\Assign;
use App\Models\V2\FloatingIp;
use App\Resources\V2\FloatingIpResource;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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
        //$collection = FloatingIp::forUser($request->user)

        // "resource_id" filtering hack - start
        if ($request->has('resource_id:eq')) {
            if ($request->get('resource_id:eq') === 'null') {
                $floatingIpIds = FloatingIp::forUser($request->user)->get()
                    ->reject(function ($floatingIp) {
                        return $floatingIp->resource_id != null;
                    })
                    ->map(function ($floatingIp) {
                        return $floatingIp->id;
                    });
                $collection = FloatingIp::whereIn('id', $floatingIpIds);
            } else {
                $resourceId = $request->get('resource_id:eq');
                $floatingIpIds = FloatingIp::forUser($request->user)->get()
                    ->reject(function ($floatingIp) use ($resourceId) {
                        return $floatingIp->resource_id != $resourceId;
                    })
                    ->map(function ($floatingIp) {
                        return $floatingIp->id;
                    });
                $collection = FloatingIp::whereIn('id', $floatingIpIds);
            }
            $request->query->remove('resource_id:eq');  // So Ditto doesn't try to filter by resource_id
        } elseif ($request->has('resource_id:in')) {
            $ids = explode(',', $request->get('resource_id:in'));
            $floatingIpIds = FloatingIp::forUser($request->user)->get()
                ->reject(function ($floatingIp) use ($ids) {
                    return !in_array($floatingIp->resource_id, $ids);
                })
                ->map(function ($floatingIp) {
                    return $floatingIp->id;
                });
            $collection = FloatingIp::whereIn('id', $floatingIpIds);
            $request->query->remove('resource_id:in');  // So Ditto doesn't try to filter by resource_id
        } else {
            $collection = FloatingIp::forUser($request->user);
        }
        // "resource_id" filtering hack - end

        $queryTransformer->config(FloatingIp::class)
            ->transform($collection);

        return FloatingIpResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    /**
     * @param Request $request
     * @param string fipId
     * @return FloatingIpResource
     */
    public function show(Request $request, string $fipId)
    {
        return new FloatingIpResource(
            FloatingIp::forUser($request->user)->findOrFail($fipId)
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

    /**
     * @param AssignRequest $request
     * @param string $fipId
     * @return Response|\Laravel\Lumen\Http\ResponseFactory
     */
    public function assign(AssignRequest $request, string $fipId)
    {
        $this->dispatch(new Assign([
            'floating_ip_id' => $fipId,
            'resource_id' => $request->resource_id
        ]));

        return response(null, 202);
    }

    /**
     * @param Request $request
     * @param string $fipId
     * @return Response
     */
    public function unassign(Request $request, string $fipId)
    {
        $floatingIp = FloatingIp::forUser($request->user)->findOrFail($fipId);
        if ($floatingIp->nat) {
            $floatingIp->nat->delete();
        }

        return new Response(null, 202);
    }
}
