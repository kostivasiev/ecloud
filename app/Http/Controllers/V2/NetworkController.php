<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\Network\CreateRequest;
use App\Http\Requests\V2\Network\UpdateRequest;
use App\Jobs\Nsx\Network\Undeploy;
use App\Models\V2\Network;
use App\Models\V2\Nic;
use App\Resources\V2\NetworkResource;
use App\Resources\V2\NicResource;
use Illuminate\Http\Request;
use UKFast\DB\Ditto\QueryTransformer;

/**
 * Class NetworkController
 * @package App\Http\Controllers\V2
 */
class NetworkController extends BaseController
{
    /**
     * @param Request $request
     * @param QueryTransformer $queryTransformer
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, QueryTransformer $queryTransformer)
    {
        if ($request->has('vpc_id:eq')) {
            if ($request->get('vpc_id:eq') === 'null') {
                $networkIds = Network::forUser($request->user)->get()
                    ->reject(function ($network) {
                        return !$network->router || $network->router->vpc_id != null;
                    })
                    ->map(function ($network) {
                        return $network->id;
                    });
                $collection = Network::whereIn('id', $networkIds);
            } else {
                $vpcId = $request->get('vpc_id:eq');
                $networkIds = Network::forUser($request->user)->get()
                    ->reject(function ($network) use ($vpcId) {
                        return !$network->router || $network->router->vpc_id != $vpcId;
                    })
                    ->map(function ($network) {
                        return $network->id;
                    });
                $collection = Network::whereIn('id', $networkIds);
            }
            $request->query->remove('vpc_id:eq');
        } elseif ($request->has('vpc_id:in')) {
            $ids = explode(',', $request->get('vpc_id:in'));
            $networkIds = Network::forUser($request->user)->get()
                ->reject(function ($network) use ($ids) {
                    return !$network->router || !in_array($network->router->vpc_id, $ids);
                })
                ->map(function ($network) {
                    return $network->id;
                });
            $collection = Network::whereIn('id', $networkIds);
            $request->query->remove('vpc_id:in');
        } else {
            $collection = Network::forUser($request->user);
        }

        $queryTransformer->config(Network::class)
            ->transform($collection);

        return NetworkResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    /**
     * @param Request $request
     * @param string $networkId
     * @return NetworkResource
     */
    public function show(Request $request, string $networkId)
    {
        return new NetworkResource(
            Network::forUser($request->user)->findOrFail($networkId)
        );
    }

    /**
     * @param CreateRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(CreateRequest $request)
    {
        $network = new Network($request->only([
            'router_id',
            'name',
            'subnet',
        ]));
        $network->save();
        $network->refresh();
        return $this->responseIdMeta($request, $network->getKey(), 201);
    }

    /**
     * @param UpdateRequest $request
     * @param string $networkId
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateRequest $request, string $networkId)
    {
        $network = Network::forUser(app('request')->user)->findOrFail($networkId);
        $network->fill($request->only([
            'router_id',
            'name',
            'subnet',
        ]));
        if (!$network->save()) {
            return $network->getSyncError();
        }
        return $this->responseIdMeta($request, $network->getKey(), 200);
    }

    /**
     * @param Request $request
     * @param string $networkId
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function destroy(Request $request, string $networkId)
    {
        $model = Network::forUser($request->user)->findOrFail($networkId);

        if (!$model->canDelete()) {
            return $model->getDeletionError();
        }

        if (!$model->delete()) {
            return $model->getSyncError();
        }

        return response()->json([], 204);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param QueryTransformer $queryTransformer
     * @param string $zoneId
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|\Illuminate\Support\HigherOrderTapProxy|mixed
     */
    public function nics(Request $request, QueryTransformer $queryTransformer, string $zoneId)
    {
        $collection = Network::forUser($request->user)->findOrFail($zoneId)->nics();
        $queryTransformer->config(Nic::class)
            ->transform($collection);

        return NicResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }
}
