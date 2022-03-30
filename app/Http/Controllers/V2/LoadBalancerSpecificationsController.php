<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\LoadBalancerSpecification\Create;
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
    public function index(Request $request)
    {
        $collection = LoadBalancerSpecification::query();

        return LoadBalancerSpecificationResource::collection(
            $collection
                ->search()
                ->orderBy('node_count')
                ->orderBy('cpu')
                ->orderBy('ram')
                ->orderBy('hdd')
                ->paginate(
                    $request->input('per_page', env('PAGINATION_LIMIT'))
                )
        );
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
     * @param Create $request
     * @return JsonResponse
     */
    public function create(Create $request)
    {
        $availabilityZoneCapacity = new LoadBalancerSpecification($request->only([
            'name',
            'description',
            'node_count',
            'cpu',
            'ram',
            'hdd',
            'iops',
            'image_id',
        ]));
        $availabilityZoneCapacity->save();
        return $this->responseIdMeta($request, $availabilityZoneCapacity->id, 201);
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
            'description',
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

    public function destroy(Request $request, string $lbsId)
    {
        $loadBalancerSpecification = LoadBalancerSpecification::findOrFail($lbsId);
        $loadBalancerSpecification->delete();

        return response('', 204);
    }
}
