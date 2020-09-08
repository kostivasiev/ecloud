<?php

namespace App\Http\Controllers\V2;

use App\Events\V2\RouterAvailabilityZoneAttach;
use App\Http\Requests\V2\CreateVpcRequest;
use App\Http\Requests\V2\UpdateVpcRequest;
use App\Models\V2\Network;
use App\Models\V2\Vpc;
use App\Resources\V2\VpcResource;
use Illuminate\Http\Request;
use UKFast\DB\Ditto\QueryTransformer;

/**
 * Class VpcController
 * @package App\Http\Controllers\V2
 */
class VpcController extends BaseController
{
    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $collection = Vpc::forUser($request->user);
        (new QueryTransformer($request))
            ->config(Vpc::class)
            ->transform($collection);

        return VpcResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param string $vpcId
     * @return VpcResource
     */
    public function show(Request $request, string $vpcId)
    {
        return new VpcResource(
            Vpc::forUser($request->user)->findOrFail($vpcId)
        );
    }

    /**
     * @param \App\Http\Requests\V2\CreateVpcRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(CreateVpcRequest $request)
    {
        $virtualPrivateClouds = new Vpc($request->only(['name', 'region_id']));
        $virtualPrivateClouds->reseller_id = $this->resellerId;
        $virtualPrivateClouds->save();
        return $this->responseIdMeta($request, $virtualPrivateClouds->getKey(), 201);
    }

    /**
     * @param \App\Http\Requests\V2\UpdateVpcRequest $request
     * @param string $vpcId
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateVpcRequest $request, string $vpcId)
    {
        $vpc = Vpc::forUser(app('request')->user)->findOrFail($vpcId);
        $vpc->name = $request->input('name', $vpc->name);
        $vpc->region_id = $request->input('region_id', $vpc->region_id);

        if ($this->isAdmin) {
            $vpc->reseller_id = $request->input('reseller_id', $vpc->reseller_id);
        }
        $vpc->save();
        return $this->responseIdMeta($request, $vpc->getKey(), 200);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param string $vpcId
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, string $vpcId)
    {
        Vpc::forUser($request->user)->findOrFail($vpcId)->delete();
        return response()->json([], 204);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param string $vpcId
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function deployDefaults(Request $request, string $vpcId)
    {
        $vpc = Vpc::forUser($request->user)->findOrFail($vpcId);

        $availabilityZone = $vpc->region()->first()->availabilityZones()->first();

        // Create a new router
        $router = $vpc->routers()->create();
        $router->availabilityZone()->associate($availabilityZone);
        $router->save();

        // Create a new network
        $network = Network::withoutEvents(function () {
            $instance = new Network();
            $instance::addCustomKey($instance);
            $instance->name = $instance->id;
            return $instance;
        });
        $network->availabilityZone()->associate($availabilityZone);
        $network->router()->associate($router);
        $network->save();

        // Deploy router and network
        event(new RouterAvailabilityZoneAttach($router, $availabilityZone));

        return response()->json([], 202);
    }
}
