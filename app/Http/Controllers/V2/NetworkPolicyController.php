<?php
namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\NetworkPolicy\Create;
use App\Http\Requests\V2\NetworkPolicy\Update;
use App\Models\V2\NetworkPolicy;
use App\Resources\V2\NetworkPolicyResource;
use Illuminate\Http\Request;
use UKFast\DB\Ditto\QueryTransformer;

class NetworkPolicyController extends BaseController
{
    public function index(Request $request, QueryTransformer $queryTransformer)
    {
        $collection = NetworkPolicy::forUser($request->user);
        $queryTransformer->config(NetworkPolicy::class)
            ->transform($collection);

        return NetworkPolicyResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    public function show(Request $request, string $networkPolicyId)
    {
        return new NetworkPolicyResource(NetworkPolicy::forUser($request->user)->findOrFail($networkPolicyId));
    }

    public function store(Create $request)
    {
        $networkPolicy = app()->make(NetworkPolicy::class);
        $networkPolicy->fill($request->only([
            'name',
            'network_id',
        ]));
        $networkPolicy->save();
        return $this->responseIdMeta($request, $networkPolicy->getKey(), 201);
    }

    public function update(Update $request, string $networkPolicyId)
    {
        $networkPolicy = NetworkPolicy::forUser(app('request')->user)->findOrFail($networkPolicyId);
        $networkPolicy->fill($request->only([
            'name',
            'network_id',
        ]));
        $networkPolicy->save();
        return $this->responseIdMeta($request, $networkPolicy->getKey(), 200);
    }

    public function destroy(Request $request, string $networkPolicyId)
    {
        $model = NetworkPolicy::forUser(app('request')->user)->findOrFail($networkPolicyId);
        if (!$model->delete()) {
            return $model->getSyncError();
        }
        return response('', 204);
    }
}
