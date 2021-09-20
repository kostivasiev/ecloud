<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\VpnEndpoint\CreateRequest;
use App\Http\Requests\V2\VpnEndpoint\UpdateRequest;
use App\Models\V2\VpnEndpoint;
use App\Resources\V2\VpnEndpointResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use UKFast\DB\Ditto\QueryTransformer;

class VpnEndpointController extends BaseController
{
    public function index(Request $request)
    {
        if ($request->hasAny([
            'vpc_id',
            'vpc_id:eq', 'vpc_id:in', 'vpc_id:lk',
            'vpc_id:neq', 'vpc_id:nin', 'vpc_id:nlk',
        ])) {

            $vpnEndpointIds = VpnEndpoint::forUser($request->user())->get();

            if ($request->has('vpc_id') || $request->has('vpc_id:eq')) {
                if ($request->has('vpc_id')) {
                    $vpcId = $request->get('vpc_id');
                    $request->query->remove('vpc_id');
                } else {
                    $vpcId = $request->get('vpc_id:eq');
                    $request->query->remove('vpc_id:eq');
                }

                $vpnEndpointIds = $vpnEndpointIds->reject(function ($vpnEndpoint) use ($vpcId) {
                    return !$vpnEndpoint->vpnService->router || $vpnEndpoint->vpnService->router->vpc->id != $vpcId;
                });
            }

            if ($request->has('vpc_id:neq')) {
                $vpcId = $request->get('vpc_id:neq');
                $request->query->remove('vpc_id:neq');

                $vpnEndpointIds = $vpnEndpointIds->reject(function ($vpnEndpoint) use ($vpcId) {
                    return !$vpnEndpoint->vpnService->router || $vpnEndpoint->vpnService->router->vpc->id == $vpcId;
                });
            }

            if ($request->has('vpc_id:lk')) {
                $vpcId = $request->get('vpc_id:lk');
                $request->query->remove('vpc_id:lk');

                $vpnEndpointIds = $vpnEndpointIds->reject(function ($vpnEndpoint) use ($vpcId) {
                    return !$vpnEndpoint->vpnService->router
                        || preg_match(
                            '/' . str_replace('\*', '\S*', preg_quote($vpcId)) . '/',
                            $vpnEndpoint->vpnService->router->vpc->id
                        ) === 0;
                });
            }

            if ($request->has('vpc_id:nlk')) {
                $vpcId = $request->get('vpc_id:nlk');
                $request->query->remove('vpc_id:nlk');

                $vpnEndpointIds = $vpnEndpointIds->reject(function ($vpnEndpoint) use ($vpcId) {
                    return !$vpnEndpoint->vpnService->router
                        || preg_match(
                            '/' . str_replace('\*', '\S*', preg_quote($vpcId)) . '/',
                            $vpnEndpoint->vpnService->router->vpc->id
                        ) === 1;
                });
            }

            if ($request->has('vpc_id:in')) {
                $ids = explode(',', $request->get('vpc_id:in'));
                $request->query->remove('vpc_id:in');

                $vpnEndpointIds = $vpnEndpointIds->reject(function ($vpnEndpoint) use ($ids) {
                    return !$vpnEndpoint->vpnService->router || !in_array($vpnEndpoint->vpnService->router->vpc->id, $ids);
                });
            }

            if ($request->has('vpc_id:nin')) {
                $ids = explode(',', $request->get('vpc_id:nin'));
                $request->query->remove('vpc_id:nin');

                $vpnEndpointIds = $vpnEndpointIds->reject(function ($vpnEndpoint) use ($ids) {
                    return !$vpnEndpoint->vpnService->router || in_array($vpnEndpoint->vpnService->router->vpc->id, $ids);
                });
            }

            $collection = VpnEndpoint::whereIn('id', $vpnEndpointIds->map(function ($vpnEndpoint) {
                return $vpnEndpoint->id;
            }));
        } else {
            $collection = VpnEndpoint::forUser($request->user());
        }

        (new QueryTransformer($request))
            ->config(VpnEndpoint::class)
            ->transform($collection);

        return VpnEndpointResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    public function show(Request $request, string $vpnEndpointId)
    {
        return new VpnEndpointResource(
            VpnEndpoint::forUser($request->user())->findOrFail($vpnEndpointId)
        );
    }

    public function store(CreateRequest $request)
    {
        $vpnEndpoint = new VpnEndpoint(
            $request->only(['name', 'vpn_service_id'])
        );
        $vpnEndpoint->save();
        $task = $vpnEndpoint->syncSave([
            'floating_ip_id' => $request->get('floating_ip_id'),
        ]);
        return $this->responseIdMeta($request, $vpnEndpoint->id, 202, $task->id);
    }

    public function update(UpdateRequest $request, string $vpnEndpointId)
    {
        $vpnEndpoint = VpnEndpoint::forUser(Auth::user())->findOrFail($vpnEndpointId);
        $vpnEndpoint->fill($request->only(['name']));
        $task = $vpnEndpoint->syncSave();
        return $this->responseIdMeta($request, $vpnEndpoint->id, 202, $task->id);
    }

    public function destroy(Request $request, string $vpnEndpointId)
    {
        $vpnEndpoint = VpnEndpoint::forUser($request->user())->findOrFail($vpnEndpointId);
        $task = $vpnEndpoint->syncDelete();
        return $this->responseTaskId($task->id);
    }
}
