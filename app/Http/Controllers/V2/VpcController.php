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
        event(new BeforeCreateEvent());
        $virtualPrivateClouds = new Vpc($request->only(['name']));
        $virtualPrivateClouds->reseller_id = $this->resellerId;
        $virtualPrivateClouds->save();
        event(new AfterCreateEvent());
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
        event(new BeforeUpdateEvent());
        $vpc->fill($request->only(['name']));
        if ($this->isAdmin) {
            $vpc->reseller_id = $request->input('reseller_id', $vpc->reseller_id);
        }
        $vpc->save();
        event(new AfterUpdateEvent());
        return $this->responseIdMeta($request, $vpc->getKey(), 200);
    }


    /**
     * @param \Illuminate\Http\Request $request
     * @param string $vpcId
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, string $vpcId)
    {
        event(new BeforeDeleteEvent());
        Vpc::forUser($request->user)->findOrFail($vpcId)->delete();
        event(new AfterDeleteEvent());
        return response()->json([], 204);
    }
}
