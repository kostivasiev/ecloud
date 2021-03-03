<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\Vpc\CreateRequest;
use App\Http\Requests\V2\Vpc\UpdateRequest;
use App\Jobs\FirewallPolicy\ConfigureDefaults;
use App\Models\V2\Instance;
use App\Models\V2\LoadBalancerCluster;
use App\Models\V2\Network;
use App\Models\V2\Router;
use App\Models\V2\Volume;
use App\Models\V2\Vpc;
use App\Resources\V2\InstanceResource;
use App\Resources\V2\LoadBalancerClusterResource;
use App\Resources\V2\VolumeResource;
use App\Resources\V2\VpcResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use UKFast\DB\Ditto\QueryTransformer;

class VpcController extends BaseController
{
    public function index(Request $request)
    {
        $collection = Vpc::forUser($request->user());
        (new QueryTransformer($request))
            ->config(Vpc::class)
            ->transform($collection);

        return VpcResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    public function show(Request $request, string $vpcId)
    {
        return new VpcResource(
            Vpc::forUser($request->user())->findOrFail($vpcId)
        );
    }

    public function create(CreateRequest $request)
    {
        $vpc = new Vpc($request->only(['name', 'region_id']));
        $vpc->reseller_id = $this->resellerId;
        $vpc->save();
        return $this->responseIdMeta($request, $vpc->id, 201);
    }

    public function update(UpdateRequest $request, string $vpcId)
    {
        $vpc = Vpc::forUser(Auth::user())->findOrFail($vpcId);
        $vpc->name = $request->input('name', $vpc->name);

        if ($this->isAdmin) {
            $vpc->reseller_id = $request->input('reseller_id', $vpc->reseller_id);
        }
        $vpc->save();
        return $this->responseIdMeta($request, $vpc->id, 200);
    }

    public function destroy(Request $request, string $vpcId)
    {
        $model = Vpc::forUser($request->user())->findOrFail($vpcId);
        if (!$model->canDelete()) {
            return $model->getDeletionError();
        }
        $model->delete();
        return response()->json([], 204);
    }

    public function volumes(Request $request, QueryTransformer $queryTransformer, string $vpcId)
    {
        $collection = Vpc::forUser($request->user())->findOrFail($vpcId)->volumes();
        $queryTransformer->config(Volume::class)
            ->transform($collection);

        return VolumeResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    public function instances(Request $request, QueryTransformer $queryTransformer, string $vpcId)
    {
        $collection = Vpc::forUser($request->user())->findOrFail($vpcId)->instances();
        $queryTransformer->config(Instance::class)
            ->transform($collection);

        return InstanceResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    public function lbcs(Request $request, QueryTransformer $queryTransformer, string $vpcId)
    {
        $collection = Vpc::forUser($request->user())->findOrFail($vpcId)->loadBalancerClusters();
        $queryTransformer->config(LoadBalancerCluster::class)
            ->transform($collection);

        return LoadBalancerClusterResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    public function deployDefaults(Request $request, string $vpcId)
    {
        $vpc = Vpc::forUser($request->user())->findOrFail($vpcId);

        $availabilityZone = $vpc->region()->first()->availabilityZones()->first();

        // Create a new router
        $router = app()->make(Router::class);
        $router->vpc()->associate($vpc);
        $router->availabilityZone()->associate($availabilityZone);
        $router->save();

        // Create a new network
        $network = app()->make(Network::class);
        $network->router()->associate($router);
        $network->save();

        // Configure default firewall policies
        $this->dispatch(new ConfigureDefaults([
            'router_id' => $router->id
        ]));

        return response(null, 202);
    }
}
