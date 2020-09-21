<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\CreateInstanceRequest;
use App\Http\Requests\V2\UpdateInstanceRequest;
use App\Models\V2\Instance;
use App\Resources\V2\CredentialResource;
use App\Resources\V2\InstanceResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use UKFast\DB\Ditto\QueryTransformer;

/**
 * Class InstanceController
 * @package App\Http\Controllers\V2
 */
class InstanceController extends BaseController
{
    /**
     * Get instance collection
     * @param \Illuminate\Http\Request $request
     * @param QueryTransformer $queryTransformer
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, QueryTransformer $queryTransformer)
    {

        $collection = Instance::forUser($request->user);

        $queryTransformer->config(Instance::class)
            ->transform($collection);

        return InstanceResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param string $instanceId
     * @return InstanceResource
     */
    public function show(Request $request, string $instanceId)
    {
        return new InstanceResource(
            Instance::forUser($request->user)->findOrFail($instanceId)
        );
    }

    /**
     * @param \App\Http\Requests\V2\CreateInstanceRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(CreateInstanceRequest $request)
    {
        $instance = new Instance($request->only(['name', 'vpc_id', 'locked']));
        if (!$request->has('locked')) {
            $instance->locked = false;
        }
        $instance->save();
        $instance->refresh();
        return $this->responseIdMeta($request, $instance->getKey(), 201);
    }

    /**
     * @param UpdateInstanceRequest $request
     * @param string $instanceId
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateInstanceRequest $request, string $instanceId)
    {
        $instance = Instance::forUser(app('request')->user)->findOrFail($instanceId);
        if (!$this->isAdmin &&
            (!$request->has('locked') || $request->get('locked') !== false) &&
            $instance->locked === true) {
            return $this->isLocked();
        }
        $instance->fill($request->only(['name', 'vpc_id', 'locked']));
        $instance->save();
        return $this->responseIdMeta($request, $instance->getKey(), 200);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param string $instanceId
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, string $instanceId)
    {
        $instance = Instance::forUser($request->user)->findOrFail($instanceId);
        if (!$this->isAdmin && $instance->locked === true) {
            return $this->isLocked();
        }
        $instance->delete();
        return response()->json([], 204);
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    private function isLocked(): JsonResponse
    {
        return JsonResponse::create([
            'errors' => [
                'title'  => 'Forbidden',
                'detail' => 'The specified instance is locked',
                'status' => 403,
            ]
        ], 403);
    }

    /**
     * @param  Request  $request
     * @param  string  $instanceId
     *
     * @return AnonymousResourceCollection|\Illuminate\Support\HigherOrderTapProxy|mixed
     */
    public function credentials(Request $request, string $instanceId)
    {
        return CredentialResource::collection(
            Instance::forUser($request->user)
                ->findOrFail($instanceId)
                ->credentials()
                ->paginate($request->input('per_page', env('PAGINATION_LIMIT')))
        );
    }
}
