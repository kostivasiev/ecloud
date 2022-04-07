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
    public function index(Request $request, QueryTransformer $queryTransformer)
    {

        if ($request->hasAny([
            'vpc_id',
            'vpc_id:eq', 'vpc_id:in', 'vpc_id:lk',
            'vpc_id:neq', 'vpc_id:nin', 'vpc_id:nlk',
        ])) {
            $vpnServiceIds = VpnService::forUser($request->user())->get();

            if ($request->has('vpc_id') || $request->has('vpc_id:eq')) {
                if ($request->has('vpc_id')) {
                    $vpcId = $request->get('vpc_id');
                    $request->query->remove('vpc_id');
                } else {
                    $vpcId = $request->get('vpc_id:eq');
                    $request->query->remove('vpc_id:eq');
                }

                $vpnServiceIds = $vpnServiceIds->reject(function ($vpnService) use ($vpcId) {
                    return !$vpnService->router || $vpnService->router->vpc_id != $vpcId;
                });
            }

            if ($request->has('vpc_id:neq')) {
                $vpcId = $request->get('vpc_id:neq');
                $request->query->remove('vpc_id:neq');

                $vpnServiceIds = $vpnServiceIds->reject(function ($vpnService) use ($vpcId) {
                    return !$vpnService->router || $vpnService->router->vpc_id == $vpcId;
                });
            }

            if ($request->has('vpc_id:lk')) {
                $vpcId = $request->get('vpc_id:lk');
                $request->query->remove('vpc_id:lk');

                $vpnServiceIds = $vpnServiceIds->reject(function ($vpnService) use ($vpcId) {
                    return !$vpnService->router
                        || preg_match(
                            '/' . str_replace('\*', '\S*', preg_quote($vpcId)) . '/',
                            $vpnService->router->vpc_id
                        ) === 0;
                });
            }

            if ($request->has('vpc_id:nlk')) {
                $vpcId = $request->get('vpc_id:nlk');
                $request->query->remove('vpc_id:nlk');

                $vpnServiceIds = $vpnServiceIds->reject(function ($vpnService) use ($vpcId) {
                    return !$vpnService->router
                        || preg_match(
                            '/' . str_replace('\*', '\S*', preg_quote($vpcId)) . '/',
                            $vpnService->router->vpc_id
                        ) === 1;
                });
            }

            if ($request->has('vpc_id:in')) {
                $ids = explode(',', $request->get('vpc_id:in'));
                $request->query->remove('vpc_id:in');

                $vpnServiceIds = $vpnServiceIds->reject(function ($vpnService) use ($ids) {
                    return !$vpnService->router || !in_array($vpnService->router->vpc_id, $ids);
                });
            }

            if ($request->has('vpc_id:nin')) {
                $ids = explode(',', $request->get('vpc_id:nin'));
                $request->query->remove('vpc_id:nin');

                $vpnServiceIds = $vpnServiceIds->reject(function ($vpnService) use ($ids) {
                    return !$vpnService->router || in_array($vpnService->router->vpc_id, $ids);
                });
            }

            $collection = VpnService::whereIn('id', $vpnServiceIds->map(function ($vpnService) {
                return $vpnService->id;
            }));
        } else {
            $collection = VpnService::forUser($request->user());
        }

        $queryTransformer->config(VpnService::class)
            ->transform($collection);

        return VpnServiceResource::collection($collection->paginate(
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
