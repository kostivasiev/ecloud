<?php

namespace App\Http\Controllers\V1;

use App\Exceptions\V1\PodNotFoundException;
use App\Models\V1\GpuProfile;
use App\Models\V1\Pod;
use App\Models\V1\San;
use Illuminate\Http\Request;
use App\Services\V1\Resource\Traits\RequestHelper;
use App\Services\V1\Resource\Traits\ResponseHelper;
use UKFast\DB\Ditto\QueryTransformer;

class PodController extends BaseController
{
    use ResponseHelper, RequestHelper;

    /**
     * List all solutions
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $collectionQuery = static::getPodQuery($request);

        (new QueryTransformer($request))
            ->config(Pod::class)
            ->transform($collectionQuery);

        $pods = $collectionQuery->paginate($this->perPage);


        return $this->respondCollection(
            $request,
            $pods
        );
    }

    /**
     * Show specific solution
     *
     * @param Request $request
     * @param $podId
     * @return \Illuminate\http\Response
     * @throws PodNotFoundException
     */
    public function show(Request $request, $podId)
    {
        return $this->respondItem(
            $request,
            static::getPodById($request, $podId)
        );
    }

    /**
     * get solution by ID
     * @param Request $request
     * @param $podId
     * @return mixed
     * @throws PodNotFoundException
     */
    public static function getPodById(Request $request, $podId)
    {
        $pod = static::getPodQuery($request)->find($podId);
        if (is_null($pod)) {
            throw new PodNotFoundException('Pod ID #' . $podId . ' not found');
        }

        return $pod;
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public static function getPodQuery(Request $request)
    {
        $podQuery = Pod::query();
        if ($request->user()->isScoped()) {
            $podQuery->where('ucs_datacentre_active', 'Yes');
            $podQuery->where('ucs_datacentre_api_enabled', 'Yes');

            $podQuery->whereIn('ucs_datacentre_reseller_id', [0, $request->user()->resellerId()]);
        }
        return $podQuery;
    }

    /**
     * List available GPU Profiles
     * @param Request $request
     * @param $podId
     * @return \Illuminate\Http\Response
     * @throws PodNotFoundException
     */
    public function gpuProfiles(Request $request, $podId)
    {
        $pod = static::getPodById($request, $podId);

        $profiles = $pod->gpuProfiles()->getQuery();

        (new QueryTransformer($request))
            ->config(GpuProfile::class)
            ->transform($profiles);

        $profiles = $profiles->paginate($this->perPage);

        return $this->respondCollection(
            $request,
            $profiles,
            200,
            null,
            [],
            ($this->isAdmin) ? null : GpuProfile::VISIBLE_SCOPE_RESELLER
        );
    }

    /**
     * Return a list od SANS available to the Pod
     * @param Request $request
     * @param $podId
     * @return \Illuminate\Http\Response
     * @throws PodNotFoundException
     */
    public function indexStorage(Request $request, $podId)
    {
        $pod = static::getPodById($request, $podId);

        $sans = $pod->sans()->getQuery();

        (new QueryTransformer($request))
            ->config(San::class)
            ->transform($sans);

        $sans = $sans->paginate($this->perPage);

        return $this->respondCollection(
            $request,
            $sans,
            200
        );
    }

    public function consoleAvailable(Request $request, $podId)
    {
        $pod = static::getPodById($request, $podId);
        $consoleResource = $pod->resource('console');
        if (!$consoleResource) {
            return response('', 404);
        }
        return response('', 200);
    }

    public function resource(Request $request, $podId)
    {
        $pod = static::getPodById($request, $podId);
        $resources = [];
        foreach ($pod->resources() as $resource) {
            $resources[] = [
                $resource->toArray() + [
                    'type' => array_search(get_class($resource), $pod->resource_types)
                ],
            ];
        }
        return response([
            'data' => $resources,
            'meta' => [],
        ]);
    }

    public function resourceTypes(Request $request, $podId)
    {
        $pod = static::getPodById($request, $podId);
        return response([
            'data' => array_keys($pod->resource_types),
            'meta' => [],
        ]);
    }

    public function resourceAdd(Request $request, $podId)
    {
        $pod = static::getPodById($request, $podId);
        $resource = $pod->resource_types[$request->type]::Create();
        $pod->addResource($resource);
        return response([
            'data' => [
                'id' => $resource->id,
            ],
            'meta' => [],
        ], 201);
    }

    public function resourceRemove(Request $request, $podId, $resourceId)
    {
        $pod = static::getPodById($request, $podId);
        $resources = array_filter($pod->resources(), function ($v, $k) use ($resourceId) {
            return $v = $resourceId;
        }, ARRAY_FILTER_USE_BOTH);
        $resource = array_shift($resources);
        $pod->removeResource($resource);
        return response('', 204);
    }

    public function resourceUpdate(Request $request, $podId, $resourceId)
    {
        $pod = static::getPodById($request, $podId);
        $resources = array_filter($pod->resources(), function ($v, $k) use ($resourceId) {
            return $v->id == $resourceId;
        }, ARRAY_FILTER_USE_BOTH);
        $resource = array_shift($resources);
        $resource->fill($request->post() ?? [] + $resource->toArray())
            ->save();

        return response([
            'data' => $resource->toArray(),
            'meta' => [],
        ]);
    }
}
