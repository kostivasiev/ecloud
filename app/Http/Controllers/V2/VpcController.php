<?php

namespace App\Http\Controllers\V2;

use App\Events\V2\Vpc\AfterCreateEvent;
use App\Events\V2\Vpc\AfterDeleteEvent;
use App\Events\V2\Vpc\AfterUpdateEvent;
use App\Events\V2\Vpc\BeforeCreateEvent;
use App\Events\V2\Vpc\BeforeDeleteEvent;
use App\Events\V2\Vpc\BeforeUpdateEvent;
use App\Http\Requests\V2\CreateVpcRequest;
use App\Http\Requests\V2\UpdateVpcRequest;
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
        $collection = Vpc::query();
        if (!$request->user->isAdministrator) {
            $collection = $collection->withReseller($request->user->resellerId);
        }

        (new QueryTransformer($request))
            ->config(Vpc::class)
            ->transform($collection);

        return VpcResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param string $vpcUuid
     * @return VpcResource
     */
    public function show(Request $request, string $vpcUuid)
    {
        $vpc = Vpc::query();
        if (!$request->user->isAdministrator) {
            $vpc->withReseller($request->user->resellerId);
        }

        return new VpcResource(
            $vpc->findOrFail($vpcUuid)
        );
    }

    /**
     * @param \App\Http\Requests\V2\CreateVpcRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(CreateVpcRequest $request)
    {
        event(new BeforeCreateEvent());
        $request->user = app('request')->user;
        $virtualPrivateClouds = new Vpc($request->only(['name']));
        $virtualPrivateClouds->reseller_id = $request->user->resellerId;
        $virtualPrivateClouds->save();
        event(new AfterCreateEvent());
        return $this->responseIdMeta($request, $virtualPrivateClouds->getKey(), 201);
    }

    /**
     * @param \App\Http\Requests\V2\UpdateVpcRequest $request
     * @param string $vpcUuid
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateVpcRequest $request, string $vpcUuid)
    {
        $vpc = Vpc::query();
        $request->user = app('request')->user;
        if (!$request->user->isAdministrator) {
            $vpc->withReseller($request->user->resellerId);
        }
        $virtualPrivateCloud = $vpc->findOrFail($vpcUuid);

        event(new BeforeUpdateEvent());
        $virtualPrivateCloud->fill($request->only(['name']));
        if ($request->user->isAdministrator) {
            $virtualPrivateCloud->reseller_id = $request->input('reseller_id', $virtualPrivateCloud->reseller_id);
        }
        $virtualPrivateCloud->save();
        event(new AfterUpdateEvent());
        return $this->responseIdMeta($request, $virtualPrivateCloud->getKey(), 200);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param string $vdcUuid
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, string $vdcUuid)
    {
        $vpc = Vpc::query();
        if (!$request->user->isAdministrator) {
            $vpc->withReseller($request->user->resellerId);
        }
        $virtualPrivateCloud = $vpc->findOrFail($vdcUuid);

        event(new BeforeDeleteEvent());
        $virtualPrivateCloud->delete();
        event(new AfterDeleteEvent());
        return response()->json([], 204);
    }
}
