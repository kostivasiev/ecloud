<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\Router\CreateRequest;
use App\Http\Requests\V2\Router\UpdateRequest;
use App\Jobs\Router\ConfigureRouterDefaults;
use App\Models\V2\Router;
use App\Models\V2\VpnService;
use App\Resources\V2\FirewallPolicyResource;
use App\Resources\V2\NetworkResource;
use App\Resources\V2\RouterResource;
use App\Resources\V2\TaskResource;
use App\Resources\V2\VpnServiceResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use UKFast\DB\Ditto\QueryTransformer;

class RouterController extends BaseController
{
    public function index(Request $request)
    {
        $collection = Router::forUser($request->user());

        return RouterResource::collection($collection->search()->paginate(
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

        if ($request->user()->isAdmin()) {
            $router->is_management = $request->input('is_management', false);
        }

        $task = $router->syncSave();
        return $this->responseIdMeta($request, $router->id, 202, $task->id);
    }

    public function update(UpdateRequest $request, string $routerId)
    {
        $router = Router::forUser(Auth::user())->findOrFail($routerId);
        $router->fill($request->only(['name', 'router_throughput_id']));

        if ($request->user()->isAdmin()) {
            $router->is_management = $request->input('is_management', $router->is_management);
        }

        $task = $router->syncSave();
        return $this->responseIdMeta($request, $router->id, 202, $task->id);
    }

    public function destroy(Request $request, string $routerId)
    {
        $task = Router::forUser($request->user())->findOrFail($routerId)->syncDelete();
        return $this->responseTaskId($task->id);
    }

    public function vpns(Request $request, QueryTransformer $queryTransformer, string $routerId)
    {
        $collection = Router::forUser($request->user())->findOrFail($routerId)->vpns();
        $queryTransformer->config(VpnService::class)
            ->transform($collection);

        return VpnServiceResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    public function networks(Request $request, string $routerId)
    {
        $collection = Router::forUser($request->user())->findOrFail($routerId)->networks();

        return NetworkResource::collection($collection->search()->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    public function configureDefaultPolicies(Request $request, string $routerId)
    {
        $router = Router::forUser($request->user())->findOrFail($routerId);

        $this->dispatch(new ConfigureRouterDefaults($router));

        return response('', 202);
    }

    public function firewallPolicies(Request $request, string $routerId)
    {
        $collection = Router::forUser($request->user())->findOrFail($routerId)->firewallPolicies();

        return FirewallPolicyResource::collection(
            $collection->search()
                ->paginate(
                    $request->input('per_page', env('PAGINATION_LIMIT'))
                )
        );
    }

    public function tasks(Request $request, string $routerId)
    {
        $collection = Router::forUser($request->user())->findOrFail($routerId)->tasks();

        return TaskResource::collection($collection->search()->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }
}
