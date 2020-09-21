<?php

namespace App\Http\Controllers\V2;

use Illuminate\Http\Request;
use App\Http\Requests\V2\Instance\CreateRequest;
use App\Http\Requests\V2\Instance\DeployRequest;
use App\Http\Requests\V2\Instance\UpdateRequest;
use App\Models\V2\Instance;
use App\Resources\V2\InstanceResource;
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
     * @param  CreateRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(CreateRequest $request)
    {
        $instance = new Instance($request->only(['network_id', 'name']));
        $instance->save();
        $instance->refresh();
        return $this->responseIdMeta($request, $instance->getKey(), 201);
    }

    /**
     * @param UpdateRequest $request
     * @param string $instanceId
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateRequest $request, string $instanceId)
    {
        $instance = Instance::forUser(app('request')->user)->findOrFail($instanceId);
        $instance->fill($request->only(['vpc_id', 'name']));
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
        $instance->delete();
        return response()->json([], 204);
    }

    public function deploy(DeployRequest $request, string $instanceId)
    {
        $instance = Instance::forUser($request->user)->findOrFail($instanceId);
        if (!$instance) {
            return response()->json([], 404);
        }

        $data = [
            'instance_id' => $instance->id,
            'vpc_id' => $instance->vpc->id,
            'volume_capacity' => $request->input('volume_capacity', config('volume.capacity.min')),
            'network_id' => $request->input('network_id'),
            'floating_ip_id' => $request->input('floating_ip_id'),
            'appliance_data' => $request->input('appliance_data'),
        ];

        // Create the chained jobs for deployment
        $this->dispatch((new \App\Jobs\Instance\Deploy\Deploy($data))->chain([
            new \App\Jobs\Instance\Deploy\UpdateNetworkAdapter($data),
            new \App\Jobs\Instance\Deploy\PowerOn($data),
            new \App\Jobs\Instance\Deploy\WaitOsCustomisation($data),
            new \App\Jobs\Instance\Deploy\PrepareOsUsers($data),
            new \App\Jobs\Instance\Deploy\OsCustomisation($data),
            new \App\Jobs\Instance\Deploy\PrepareOsDisk($data),
            new \App\Jobs\Instance\Deploy\RunApplianceBootstrap($data),
            new \App\Jobs\Instance\Deploy\RunBootstrapScript($data),
        ]));

        return response()->json([], 200);
    }
}
