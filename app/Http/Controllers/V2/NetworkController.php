<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\Network\CreateRequest;
use App\Http\Requests\V2\Network\UpdateRequest;
use App\Models\V2\Network;
use App\Models\V2\Nic;
use App\Models\V2\Task;
use App\Resources\V2\NetworkResource;
use App\Resources\V2\NicResource;
use App\Resources\V2\TaskResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        if ($request->hasAny([
            'vpc_id',
            'vpc_id:eq', 'vpc_id:in', 'vpc_id:lk',
            'vpc_id:neq', 'vpc_id:nin', 'vpc_id:nlk',
        ])) {
            $networkIds = Network::forUser($request->user())->get();

            if ($request->has('vpc_id') || $request->has('vpc_id:eq')) {
                if ($request->has('vpc_id')) {
                    $vpcId = $request->get('vpc_id');
                    $request->query->remove('vpc_id');
                } else {
                    $vpcId = $request->get('vpc_id:eq');
                    $request->query->remove('vpc_id:eq');
                }

                $networkIds = $networkIds->reject(function ($network) use ($vpcId) {
                    return !$network->router || $network->router->vpc_id != $vpcId;
                });
            }

            if ($request->has('vpc_id:neq')) {
                $vpcId = $request->get('vpc_id:neq');
                $request->query->remove('vpc_id:neq');

                $networkIds = $networkIds->reject(function ($network) use ($vpcId) {
                    return !$network->router || $network->router->vpc_id == $vpcId;
                });
            }

            if ($request->has('vpc_id:lk')) {
                $vpcId = $request->get('vpc_id:lk');
                $request->query->remove('vpc_id:lk');

                $networkIds = $networkIds->reject(function ($network) use ($vpcId) {
                    return !$network->router
                        || preg_match(
                            '/' . str_replace('\*', '\S*', preg_quote($vpcId)) . '/',
                            $network->router->vpc_id
                        ) === 0;
                });
            }

            if ($request->has('vpc_id:nlk')) {
                $vpcId = $request->get('vpc_id:nlk');
                $request->query->remove('vpc_id:nlk');

                $networkIds = $networkIds->reject(function ($network) use ($vpcId) {
                    return !$network->router
                        || preg_match(
                            '/' . str_replace('\*', '\S*', preg_quote($vpcId)) . '/',
                            $network->router->vpc_id
                        ) === 1;
                });
            }

            if ($request->has('vpc_id:in')) {
                $ids = explode(',', $request->get('vpc_id:in'));
                $request->query->remove('vpc_id:in');

                $networkIds = $networkIds->reject(function ($network) use ($ids) {
                    return !$network->router || !in_array($network->router->vpc_id, $ids);
                });
            }

            if ($request->has('vpc_id:nin')) {
                $ids = explode(',', $request->get('vpc_id:nin'));
                $request->query->remove('vpc_id:nin');

                $networkIds = $networkIds->reject(function ($network) use ($ids) {
                    return !$network->router || in_array($network->router->vpc_id, $ids);
                });
            }

            $collection = Network::whereIn('id', $networkIds->map(function ($network) {
                return $network->id;
            }));
        } else {
            $collection = Network::forUser($request->user());
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
            Network::forUser($request->user())->findOrFail($networkId)
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

        $task = $network->syncSave();
        return $this->responseIdMeta($request, $network->id, 202, $task->id);
    }

    /**
     * @param UpdateRequest $request
     * @param string $networkId
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateRequest $request, string $networkId)
    {
        $network = Network::forUser(Auth::user())->findOrFail($networkId);
        $network->fill($request->only([
            'name',
        ]));

        $task = $network->syncSave();
        return $this->responseIdMeta($request, $network->id, 202, $task->id);
    }

    public function destroy(Request $request, string $networkId)
    {
        $task = Network::forUser($request->user())->findOrFail($networkId)->syncDelete();
        return $this->responseTaskId($task->id);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param QueryTransformer $queryTransformer
     * @param string $networkId
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|\Illuminate\Support\HigherOrderTapProxy|mixed
     */
    public function nics(Request $request, QueryTransformer $queryTransformer, string $networkId)
    {
        $collection = Network::forUser($request->user())->findOrFail($networkId)->nics();
        $queryTransformer->config(Nic::class)
            ->transform($collection);

        return NicResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    public function tasks(Request $request, QueryTransformer $queryTransformer, string $networkId)
    {
        $collection = Network::forUser($request->user())->findOrFail($networkId)->tasks();
        $queryTransformer->config(Task::class)
            ->transform($collection);

        return TaskResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }
}
