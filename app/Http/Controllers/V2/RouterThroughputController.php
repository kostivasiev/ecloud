<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\RouterThroughput\CreateRequest;
use App\Http\Requests\V2\RouterThroughput\UpdateRequest;
use App\Models\V2\RouterThroughput;
use App\Resources\V2\RouterThroughputResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use UKFast\DB\Ditto\QueryTransformer;

class RouterThroughputController extends BaseController
{
    /**
     * @param Request $request
     * @param QueryTransformer $queryTransformer
     * @return AnonymousResourceCollection
     */
    public function index(Request $request, QueryTransformer $queryTransformer): AnonymousResourceCollection
    {
        $collection = RouterThroughput::query();
        $queryTransformer->config(RouterThroughput::class)
            ->transform($collection);

        return RouterThroughputResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    /**
     * @param Request $request
     * @param string $routerThroughputId
     * @return RouterThroughputResource
     */
    public function show(Request $request, string $routerThroughputId): RouterThroughputResource
    {
        return new RouterThroughputResource(
            RouterThroughput::findOrFail($routerThroughputId)
        );
    }

    /**
     * @param CreateRequest $request
     * @return JsonResponse
     */
    public function store(CreateRequest $request): JsonResponse
    {
        $routerThroughput = new RouterThroughput($request->only([
            'name',
            'availability_zone_id',
            'committed_bandwidth',
            'burst_size'
        ]));
        $routerThroughput->save();
        return $this->responseIdMeta($request, $routerThroughput->getKey(), 201);
    }

    /**
     * @param UpdateRequest $request
     * @param string $routerThroughputId
     * @return JsonResponse
     */
    public function update(UpdateRequest $request, string $routerThroughputId): JsonResponse
    {
        $routerThroughput = RouterThroughput::findOrFail($routerThroughputId);
        $routerThroughput->fill($request->only([
            'name',
            'availability_zone_id',
            'committed_bandwidth',
            'burst_size'
        ]));
        $routerThroughput->save();
        return $this->responseIdMeta($request, $routerThroughput->getKey(), 200);
    }

    /**
     * @param Request $request
     * @param string $routerThroughputId
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, string $routerThroughputId): Response
    {
        $routerThroughput = RouterThroughput::findOrFail($routerThroughputId);
        $routerThroughput->delete();
        return response(null, 204);
    }
}
