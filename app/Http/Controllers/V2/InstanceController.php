<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\CreateInstanceRequest;
use App\Http\Requests\V2\UpdateInstanceRequest;
use App\Jobs\InstanceDeploy\OsCustomisation;
use App\Jobs\InstanceDeploy\PowerOn;
use App\Jobs\InstanceDeploy\PrepareOsDisk;
use App\Jobs\InstanceDeploy\PrepareOsUsers;
use App\Jobs\InstanceDeploy\RunApplianceBootstrap;
use App\Jobs\InstanceDeploy\RunBootstrapScript;
use App\Jobs\InstanceDeploy\UpdateNetworkAdapter;
use App\Jobs\InstanceDeploy\WaitOsCustomisation;
use App\Models\V2\Instance;
use App\Resources\V2\InstanceResource;
use Illuminate\Http\Request;
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
        $collection = Instance::query();

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
            Instance::findOrFail($instanceId)
        );
    }

    /**
     * @param \App\Http\Requests\V2\CreateInstanceRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(CreateInstanceRequest $request)
    {
        $instance = new Instance($request->only(['network_id', 'name']));
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
        $instance = Instance::findOrFail($instanceId);
        $instance->fill($request->only(['network_id', 'name']));
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
        $instance = Instance::findOrFail($instanceId);
        $instance->delete();
        return response()->json([], 204);
    }

    public function deploy(Request $request, string $instanceId)
    {
        $instance = Instance::findOrFail($instanceId);  // TODO :- User scope

        $data = [];

        // Create the jobs for deployment
        $this->dispatch((new UpdateNetworkAdapter($data))->chain([
            new PowerOn($data),
            new WaitOsCustomisation($data),
            new PrepareOsUsers($data),
            new OsCustomisation($data),
            new PrepareOsDisk($data),
            new RunApplianceBootstrap($data),
            new RunBootstrapScript($data),
        ]));

        return response()->json([], 200);
    }
}
