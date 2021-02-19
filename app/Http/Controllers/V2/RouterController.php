<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\Router\CreateRequest;
use App\Http\Requests\V2\Router\UpdateRequest;
use App\Jobs\FirewallPolicy\ConfigureDefaults;
use App\Models\V2\FirewallPolicy;
use App\Models\V2\Network;
use App\Models\V2\Router;
use App\Models\V2\Vpn;
use App\Resources\V2\FirewallPolicyResource;
use App\Resources\V2\NetworkResource;
use App\Resources\V2\RouterResource;
use App\Resources\V2\VpnResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use UKFast\DB\Ditto\QueryTransformer;

class RouterController extends BaseController
{
    public function index(Request $request)
    {
        $collection = Router::forUser($request->user());

        (new QueryTransformer($request))
            ->config(Router::class)
            ->transform($collection);

        return RouterResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    public function show(Request $request, string $routerId)
    {
        return new RouterResource(
            Router::forUser($request->user())->findOrFail($routerId)
        );
    }

    public function create(CreateRequest $request)
    {
        $router = new Router($request->only(['name', 'vpc_id', 'availability_zone_id', 'router_throughput_id']));
        $router->save();
        return $this->responseIdMeta($request, $router->getKey(), 201);
    }

    public function update(UpdateRequest $request, string $routerId)
    {
        $router = Router::forUser(Auth::user())->findOrFail($routerId);
        $router->fill($request->only(['name', 'vpc_id', 'availability_zone_id', 'router_throughput_id']));
        if (!$router->save()) {
            return $router->getSyncError();
        }
        return $this->responseIdMeta($request, $router->getKey(), 200);
    }

    public function destroy(Request $request, string $routerId)
    {
        $model = Router::forUser($request->user())->findOrFail($routerId);

        if (!$model->canDelete()) {
            return $model->getDeletionError();
        }
        if (!$model->delete()) {
            return $model->getSyncError();
        }
        return response()->json([], 204);
    }

    public function vpns(Request $request, QueryTransformer $queryTransformer, string $routerId)
    {
        $collection = Router::forUser($request->user())->findOrFail($routerId)->vpns();
        $queryTransformer->config(Vpn::class)
            ->transform($collection);

        return VpnResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    public function networks(Request $request, QueryTransformer $queryTransformer, string $routerId)
    {
        $collection = Router::forUser($request->user())->findOrFail($routerId)->networks();
        $queryTransformer->config(Network::class)
            ->transform($collection);

        return NetworkResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    public function configureDefaultPolicies(Request $request, string $routerId)
    {
        $router = Router::forUser($request->user())->findOrFail($routerId);

        $this->dispatch(new ConfigureDefaults([
            'router_id' => $router->id
        ]));

        return response(null, 202);
    }

    public function firewallPolicies(Request $request, QueryTransformer $queryTransformer, string $routerId)
    {
        $collection = Router::forUser($request->user())->findOrFail($routerId)->firewallPolicies();
        $queryTransformer->config(FirewallPolicy::class)
            ->transform($collection);

        return FirewallPolicyResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }
}
