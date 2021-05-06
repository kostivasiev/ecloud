<?php
namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\NetworkPolicy\Create;
use App\Http\Requests\V2\NetworkPolicy\Update;
use App\Models\V2\NetworkPolicy;
use App\Resources\V2\NetworkPolicyResource;
use App\Resources\V2\NetworkRuleResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use UKFast\DB\Ditto\QueryTransformer;

class NetworkPolicyController extends BaseController
{
    public function index(Request $request, QueryTransformer $queryTransformer)
    {
        $collection = NetworkPolicy::forUser($request->user());
        $queryTransformer->config(NetworkPolicy::class)
            ->transform($collection);

        return NetworkPolicyResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    public function show(Request $request, string $networkPolicyId)
    {
        return new NetworkPolicyResource(NetworkPolicy::forUser($request->user())->findOrFail($networkPolicyId));
    }

    public function store(Create $request)
    {
        $model = app()->make(NetworkPolicy::class);
        $model->fill($request->only([
            'name',
            'network_id',
        ]));

        $model->withTaskLock(function ($policy) {
            $policy->save();
        });

        return $this->responseIdMeta($request, $model->id, 202);
    }

    public function update(Update $request, string $networkPolicyId)
    {
        $model = NetworkPolicy::forUser(Auth::user())->findOrFail($networkPolicyId);
        $model->fill($request->only(['name']));

        $model->withTaskLock(function ($policy) {
            $policy->save();
        });

        return $this->responseIdMeta($request, $model->id, 202);
    }

    public function destroy(Request $request, string $networkPolicyId)
    {
        $model = NetworkPolicy::forUser($request->user())->findOrFail($networkPolicyId);

        $model->withTaskLock(function ($model) {
            $model->delete();
        });

        return response('', 202);
    }

    public function networkRules(Request $request, QueryTransformer $queryTransformer, string $networkPolicyId)
    {
        $collection = NetworkPolicy::forUser($request->user())->findOrFail($networkPolicyId)->networkRules();
        $queryTransformer->config(NetworkPolicy::class)
            ->transform($collection);

        return NetworkRuleResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }
}
