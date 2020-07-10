<?php

namespace App\Http\Controllers\V2;

use App\Events\V2\VirtualDataCentres\AfterCreateEvent;
use App\Events\V2\VirtualDataCentres\AfterDeleteEvent;
use App\Events\V2\VirtualDataCentres\AfterUpdateEvent;
use App\Events\V2\VirtualDataCentres\BeforeCreateEvent;
use App\Events\V2\VirtualDataCentres\BeforeDeleteEvent;
use App\Events\V2\VirtualDataCentres\BeforeUpdateEvent;
use App\Http\Requests\V2\CreateVirtualDataCentresRequest;
use App\Http\Requests\V2\UpdateVirtualDataCentresRequest;
use App\Models\V2\VirtualDataCentres;
use Illuminate\Http\Request;
use UKFast\DB\Ditto\QueryTransformer;

/**
 * Class VirtualDataCentresController
 * @package App\Http\Controllers\V2
 */
class VirtualDataCentresController extends BaseController
{
    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $collectionQuery = VirtualDataCentres::query();

        (new QueryTransformer($request))
            ->config(VirtualDataCentres::class)
            ->transform($collectionQuery);

        $virtualDataCentres = $collectionQuery->paginate($this->perPage);

        return $this->respondCollection(
            $request,
            $virtualDataCentres,
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
            VirtualDataCentres::findOrFail($vdcUuid),
            200
        );
    }

    /**
     * @param \App\Http\Requests\V2\CreateVirtualDataCentresRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(CreateVirtualDataCentresRequest $request)
    {
        event(new BeforeCreateEvent());
        $virtualDataCentres = new VirtualDataCentres($request->only(['name']));
        $virtualDataCentres->save();
        $virtualDataCentres->refresh();
        event(new AfterCreateEvent());
        return $this->responseIdMeta($request, $virtualDataCentres->getKey(), 201);
    }

    /**
     * @param \App\Http\Requests\V2\UpdateVirtualDataCentresRequest $request
     * @param string $vdcUuid
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateVirtualDataCentresRequest $request, string $vdcUuid)
    {
        event(new BeforeUpdateEvent());
        $virtualDataCentre = VirtualDataCentres::findOrFail($vdcUuid);
        $virtualDataCentre->fill($request->only(['name']));
        $virtualDataCentre->save();
        event(new AfterUpdateEvent());
        return $this->responseIdMeta($request, $virtualDataCentre->getKey(), 200);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param string $vdcUuid
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, string $vdcUuid)
    {
        event(new BeforeDeleteEvent());
        $virtualDataCentre = VirtualDataCentres::findOrFail($vdcUuid);
        $virtualDataCentre->delete();
        event(new AfterDeleteEvent());
        return response()->json([], 204);
    }
}
