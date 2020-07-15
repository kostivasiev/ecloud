<?php

namespace App\Http\Controllers\V2;

use App\Events\V2\VirtualPrivateClouds\AfterCreateEvent;
use App\Events\V2\VirtualPrivateClouds\AfterDeleteEvent;
use App\Events\V2\VirtualPrivateClouds\AfterUpdateEvent;
use App\Events\V2\VirtualPrivateClouds\BeforeCreateEvent;
use App\Events\V2\VirtualPrivateClouds\BeforeDeleteEvent;
use App\Events\V2\VirtualPrivateClouds\BeforeUpdateEvent;
use App\Http\Requests\V2\CreateVirtualPrivateCloudsRequest;
use App\Http\Requests\V2\UpdateVirtualPrivateCloudsRequest;
use App\Models\V2\VirtualPrivateClouds;
use Illuminate\Http\Request;
use UKFast\DB\Ditto\QueryTransformer;

/**
 * Class VirtualPrivateCloudController
 * @package App\Http\Controllers\V2
 */
class VirtualPrivateCloudsController extends BaseController
{
    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $collectionQuery = VirtualPrivateClouds::query();

        (new QueryTransformer($request))
            ->config(VirtualPrivateClouds::class)
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
            VirtualPrivateClouds::findOrFail($vdcUuid),
            200
        );
    }

    /**
     * @param \App\Http\Requests\V2\CreateVirtualPrivateCloudsRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(CreateVirtualPrivateCloudsRequest $request)
    {
        event(new BeforeCreateEvent());
        $virtualPrivateClouds = new VirtualPrivateClouds($request->only(['name']));
        $virtualPrivateClouds->save();
        $virtualPrivateClouds->refresh();
        event(new AfterCreateEvent());
        return $this->responseIdMeta($request, $virtualPrivateClouds->getKey(), 201);
    }

    /**
     * @param \App\Http\Requests\V2\UpdateVirtualPrivateCloudsRequest $request
     * @param string $vdcUuid
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateVirtualPrivateCloudsRequest $request, string $vdcUuid)
    {
        event(new BeforeUpdateEvent());
        $virtualPrivateCloud = VirtualPrivateClouds::findOrFail($vdcUuid);
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
        $virtualPrivateCloud = VirtualPrivateClouds::findOrFail($vdcUuid);
        $virtualPrivateCloud->delete();
        event(new AfterDeleteEvent());
        return response()->json([], 204);
    }
}
