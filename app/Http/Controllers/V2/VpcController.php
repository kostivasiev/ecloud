<?php

namespace App\Http\Controllers\V2;

use App\Events\V2\Network\Creating;
use App\Events\V2\RouterAvailabilityZoneAttach;
use App\Http\Requests\V2\CreateVpcRequest;
use App\Http\Requests\V2\UpdateVpcRequest;
use App\Listeners\V2\Network\UKFastId;
use App\Models\V2\Network;
use App\Models\V2\Vpc;
use App\Resources\V2\InstanceResource;
use App\Resources\V2\LoadBalancerClusterResource;
use App\Resources\V2\VolumeResource;
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
     * @param Request $request
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
     * @param Request $request
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
     * @param CreateVpcRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(CreateVpcRequest $request)
    {
        $vpc = new Vpc($request->only(['name', 'region_id']));
        $vpc->reseller_id = $this->resellerId;
        $vpc->save();
        return $this->responseIdMeta($request, $vpc->getKey(), 201);
    }

    /**
     * @param UpdateVpcRequest $request
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
     * @param Request $request
     * @param string $vpcId
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, string $vpcId)
    {
        Vpc::forUser($request->user)->findOrFail($vpcId)->delete();
        return response()->json([], 204);
    }

    /**
     * @param Request $request
     * @param string $vpcId
     * @return \Illuminate\Http\Response
     */
    public function volumes(Request $request, string $vpcId)
    {
        $volumes = Vpc::forUser($request->user)->findOrFail($vpcId)->volumes();
        return VolumeResource::collection($volumes->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param string $vpcId
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|\Illuminate\Support\HigherOrderTapProxy|mixed
     */
    public function instances(Request $request, string $vpcId)
    {
        $instances = Vpc::forUser($request->user)->findOrFail($vpcId)->instances();
        return InstanceResource::collection($instances->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param string $vpcId
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|\Illuminate\Support\HigherOrderTapProxy|mixed
     */
    public function lbcs(Request $request, string $vpcId)
    {
        return LoadBalancerClusterResource::collection(
            Vpc::forUser($request->user)
                ->findOrFail($vpcId)
                ->loadBalancerClusters()
                ->paginate(
                    $request->input('per_page', env('PAGINATION_LIMIT'))
                )
        );
    }

    /**
     * @param Request $request
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
        Network::withoutEvents(function () use ($router) {
            $network = new Network();

            // Force the UKFastId event to run
            $event = new Creating($network);
            $listener = app()->make(UKFastId::class);
            $listener->handle($event);

            $network->name = $network->id;
            $network->router()->associate($router);
            $network->save();
        });

        // Deploy router and network
        //event(new RouterAvailabilityZoneAttach($router, $availabilityZone));

        return response()->json([], 202);
    }
}
