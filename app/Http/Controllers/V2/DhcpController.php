<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\CreateDhcpRequest;
use App\Http\Requests\V2\UpdateDhcpRequest;
use App\Models\V2\Dhcp;
use App\Models\V2\Task;
use App\Models\V2\Vpc;
use App\Resources\V2\DhcpResource;
use App\Resources\V2\TaskResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        $collection = Dhcp::forUser($request->user());

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
    public function show(Request $request, string $dhcpId)
    {
        return new DhcpResource(
            Dhcp::forUser($request->user())->findOrFail($dhcpId)
        );
    }

    /**
     * @param CreateDhcpRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(CreateDhcpRequest $request)
    {
        $availabilityZone = Vpc::forUser(Auth::user())
            ->findOrFail($request->vpc_id)
            ->region
            ->availabilityZones
            ->first(function ($availabilityZone) use ($request) {
                return $availabilityZone->id == $request->availability_zone_id;
            });

        if (!$availabilityZone) {
            return response()->json([
                'errors' => [
                    'title' => 'Not Found',
                    'detail' => 'The specified availability zone is not available to that VPC',
                    'status' => 404,
                    'source' => 'availability_zone_id'
                ]
            ], 404);
        }

        $dhcp = new Dhcp($request->only(['name', 'vpc_id', 'availability_zone_id']));
        $dhcp->save();

        return $this->responseIdMeta($request, $dhcp->id, 202);
    }

    /**
     * @param UpdateDhcpRequest $request
     * @param string $dhcpId
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateDhcpRequest $request, string $dhcpId)
    {
        $dhcp = Dhcp::forUser($request->user())->findOrFail($dhcpId);
        $dhcp->fill($request->only(['name']));

        $dhcp->withTaskLock(function ($dhcp) {
            $dhcp->save();
        });

        return $this->responseIdMeta($request, $dhcp->id, 202);
    }

    public function destroy(Request $request, string $dhcpId)
    {
        $dhcp = Dhcp::forUser($request->user())->findOrFail($dhcpId);

        $dhcp->withTaskLock(function ($dhcp) {
            $dhcp->delete();
        });

        return response('', 202);
    }

    public function tasks(Request $request, QueryTransformer $queryTransformer, string $dhcpId)
    {
        $collection = Dhcp::forUser($request->user())->findOrFail($dhcpId)->tasks();
        $queryTransformer->config(Task::class)
            ->transform($collection);

        return TaskResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }
}
