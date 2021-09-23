<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\LoadBalancerSpecification\Update;
use App\Models\V2\LoadBalancerSpecification;
use App\Resources\V2\LoadBalancerSpecificationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use UKFast\DB\Ditto\QueryTransformer;

class LoadBalancerSpecificationsController extends BaseController
{
    /**
     * @param Request $request
     * @param QueryTransformer $queryTransformer
     * @return AnonymousResourceCollection
     */
    public function index(Request $request, QueryTransformer $queryTransformer)
    {
        $collection = LoadBalancerSpecification::query();
        $queryTransformer->config(LoadBalancerSpecification::class)
            ->transform($collection);

        return LoadBalancerSpecificationResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    /**
     * @param Request $request
     * @param string $lbsId
     * @return LoadBalancerSpecificationResource
     */
    public function show(Request $request, string $lbsId)
    {
        return new LoadBalancerSpecificationResource(
            LoadBalancerSpecification::findOrFail($lbsId)
        );
    }

    /**
     * @param Update $request
     * @param string $lbsId
     * @return JsonResponse
     */
    public function update(Update $request, string $lbsId)
    {
        $loadBalancerSpecification = LoadBalancerSpecification::findOrFail($lbsId);
        $loadBalancerSpecification->fill($request->only([
            'name',
            'node_count',
            'cpu',
            'ram',
            'hdd',
            'iops',
            'image_id'
        ]));
        $loadBalancerSpecification->save();
        return $this->responseIdMeta($request, $loadBalancerSpecification->id, 200);
    }
}
