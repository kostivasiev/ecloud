<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\CreateDhcpRequest;
use App\Http\Requests\V2\UpdateDhcpRequest;
use App\Jobs\Nsx\Dhcp\Undeploy;
use App\Models\V2\Dhcp;
use App\Models\V2\Sync;
use App\Resources\V2\DhcpResource;
use Illuminate\Http\Request;
use UKFast\DB\Ditto\QueryTransformer;

/**
 * Class DhcpController
 * @package App\Http\Controllers\V2
 */
class DhcpController extends BaseController
{
    /**
     * @param Request $request
     * @param QueryTransformer $queryTransformer
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, QueryTransformer $queryTransformer)
    {
        $collection = Dhcp::query();

        $queryTransformer->config(Dhcp::class)
            ->transform($collection);

        return DhcpResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    /**
     * @param string $dhcpId
     * @return DhcpResource
     */
    public function show(string $dhcpId)
    {
        return new DhcpResource(
            Dhcp::findOrFail($dhcpId)
        );
    }

    /**
     * @param CreateDhcpRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(CreateDhcpRequest $request)
    {
        $dhcps = new Dhcp($request->only(['vpc_id', 'availability_zone_id']));
        $dhcps->save();
        $dhcps->refresh();
        return $this->responseIdMeta($request, $dhcps->getKey(), 201);
    }

    /**
     * @param UpdateDhcpRequest $request
     * @param string $dhcpId
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateDhcpRequest $request, string $dhcpId)
    {
        $dhcp = Dhcp::findOrFail($dhcpId);
        $dhcp->fill($request->only(['vpc_id', 'availability_zone_id']));
        $dhcp->save();
        $dhcp->setSyncCompleted();
        return $this->responseIdMeta($request, $dhcp->getKey(), 200);
    }

    /**
     * @param string $dhcpId
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(string $dhcpId)
    {
        $model = Dhcp::findOrFail($dhcpId);

        if ($model->getStatus() !== 'complete') {
            return $model->getSyncError();
        }

        $sync = app()->make(Sync::class);
        $sync->resource_id = $model->id;
        $sync->completed = false;
        $sync->save();

        $this->dispatch(new Undeploy([
            'id' => $model->id,
        ]));

        return response()->json([], 204);
    }
}
