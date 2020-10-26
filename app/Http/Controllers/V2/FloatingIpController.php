<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\FloatingIp\AssignRequest;
use App\Http\Requests\V2\FloatingIp\CreateRequest;
use App\Http\Requests\V2\FloatingIp\UpdateRequest;
use App\Models\V2\FloatingIp;
use App\Models\V2\Nat;
use App\Resources\V2\FloatingIpResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use UKFast\DB\Ditto\QueryTransformer;

/**
 * Class InstanceController
 * @package App\Http\Controllers\V2
 */
class FloatingIpController extends BaseController
{
    /**
     * Get resource collection
     * @param Request $request
     * @param QueryTransformer $queryTransformer
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, QueryTransformer $queryTransformer)
    {
        $collection = FloatingIp::forUser($request->user);

        $queryTransformer->config(FloatingIp::class)
            ->transform($collection);

        return FloatingIpResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    /**
     * @param Request $request
     * @param string fipId
     * @return FloatingIpResource
     */
    public function show(Request $request, string $fipId)
    {
        return new FloatingIpResource(
            FloatingIp::forUser($request->user)->findOrFail($fipId)
        );
    }

    /**
     * @param CreateRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(CreateRequest $request)
    {
        $resource = new FloatingIp(
            $request->only(['vpc_id', 'name'])
        );
        $resource->save();
        return $this->responseIdMeta($request, $resource->getKey(), 201);
    }

    /**
     * @param UpdateRequest $request
     * @param string $instanceId
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateRequest $request, string $fipId)
    {
        $resource = FloatingIp::forUser(app('request')->user)->findOrFail($fipId);
        $resource->fill($request->only(['name']));
        $resource->save();
        return $this->responseIdMeta($request, $resource->getKey(), 200);
    }

    /**
     * @param Request $request
     * @param string $instanceId
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, string $fipId)
    {
        $resource = FloatingIp::forUser(app('request')->user)->findOrFail($fipId);
        $resource->delete();
        return response()->json([], 204);
    }

    /**
     * @param AssignRequest $request
     * @param string $fipId
     * @return Response|\Laravel\Lumen\Http\ResponseFactory
     */
    public function assign(AssignRequest $request, string $fipId)
    {
        // TODO :- Move this to the AssignRequest?
        $request['id'] = $fipId;
        $this->validate(
            $request,
            ['id' => 'unique:ecloud.nats,destination'],
            ['id.unique' => 'The floating IP is already assigned']
        );

        $fip = FloatingIp::forUser(app('request')->user)->findOrFail($fipId);

        $nat = new Nat;
        $nat->destination_id = $fip->id;
        $nat->destinationable_type = 'fip';
        $nat->translated_id = $request->resource_id;

        // TODO :- This is hack and needs addressing. The type should be discovered when assigning.
        foreach (Relation::morphMap() as $map => $model) {
            try {
                $model::forUser(app('request')->user)->findOrFail($request->resource_id);
                $nat->translatedable_type = $map;
                break;
            } catch (ModelNotFoundException $exception) {
                continue;
            }
        }

        $nat->save();

        return response(null, 200);
    }
}
