<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\VpnService\CreateRequest;
use App\Http\Requests\V2\VpnService\UpdateRequest;
use App\Models\V2\VpnService;
use App\Resources\V2\VpnEndpointResource;
use App\Resources\V2\VpnServiceResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use UKFast\DB\Ditto\QueryTransformer;

class VpnServiceController extends BaseController
{
    public function index(Request $request)
    {
        $collection = VpnService::forUser($request->user());
        return VpnServiceResource::collection($collection->search()->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    public function show(Request $request, string $vpnServiceId)
    {
        return new VpnServiceResource(
            VpnService::forUser($request->user())->findOrFail($vpnServiceId)
        );
    }

    public function create(CreateRequest $request)
    {
        $vpnService = new VpnService($request->only(['router_id', 'name']));
        $task = $vpnService->syncSave();

        return $this->responseIdMeta($request, $vpnService->id, 202, $task->id);
    }

    public function update(UpdateRequest $request, string $vpnServiceId)
    {
        $vpnService = VpnService::forUser(Auth::user())->findOrFail($vpnServiceId);
        $vpnService->fill($request->only(['name']));
        $task = $vpnService->syncSave();
        return $this->responseIdMeta($request, $vpnService->id, 202, $task->id);
    }

    public function destroy(Request $request, string $vpnServiceId)
    {
        $task = VpnService::forUser($request->user())->findOrFail($vpnServiceId)->syncDelete();
        return $this->responseTaskId($task->id);
    }

    public function endpoints(Request $request, string $vpnServiceId)
    {
        $collection = VpnService::forUser($request->user())->findOrFail($vpnServiceId)->vpnEndpoints();

        return VpnEndpointResource::collection($collection->search()->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }
}
