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
        $collectionQuery = Vpc::query();

        (new QueryTransformer($request))
            ->config(Vpc::class)
            ->transform($collectionQuery);

        $virtualPrivateClouds = $collectionQuery->paginate($this->perPage);

        return $this->respondCollection(
            $request,
            $virtualPrivateClouds,
            200
        );
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param string $vdcUuid
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, string $vdcUuid)
    {
        return $this->respondItem(
            $request,
            Vpc::findOrFail($vdcUuid),
            200
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
        $virtualPrivateClouds->save();
        $virtualPrivateClouds->refresh();
        event(new AfterCreateEvent());
        return $this->responseIdMeta($request, $virtualPrivateClouds->getKey(), 201);
    }

    /**
     * @param \App\Http\Requests\V2\UpdateVpcRequest $request
     * @param string $vdcUuid
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateVpcRequest $request, string $vdcUuid)
    {
        event(new BeforeUpdateEvent());
        $virtualPrivateCloud = Vpc::findOrFail($vdcUuid);
        $virtualPrivateCloud->fill($request->only(['name']));
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
        event(new BeforeDeleteEvent());
        $virtualPrivateCloud = Vpc::findOrFail($vdcUuid);
        $virtualPrivateCloud->delete();
        event(new AfterDeleteEvent());
        return response()->json([], 204);
    }
}
