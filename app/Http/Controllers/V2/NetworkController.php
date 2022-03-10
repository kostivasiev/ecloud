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
    public function index(Request $request)
    {
        $collection = Network::forUser($request->user());

        return NetworkResource::collection($collection->search()->paginate(
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
    public function nics(Request $request, string $networkId)
    {
        $collection = Network::forUser($request->user())->findOrFail($networkId)->nics();

        return NicResource::collection($collection->search()->paginate(
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
