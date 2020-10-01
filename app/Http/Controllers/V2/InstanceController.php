<?php

namespace App\Http\Controllers\V2;

use App\Resources\V2\VolumeResource;
use Illuminate\Http\Request;
use App\Http\Requests\V2\Instance\CreateRequest;
use App\Http\Requests\V2\Instance\DeployRequest;
use App\Http\Requests\V2\Instance\UpdateRequest;
use App\Models\V2\Instance;
use App\Resources\V2\CredentialResource;
use App\Resources\V2\InstanceResource;
use Illuminate\Http\JsonResponse;
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
        $instance = Instance::forUser($request->user)->findOrFail($instanceId);
        if ($this->isAdmin) {
            $instance->makeVisible('appliance_version_id');
        }
        return new InstanceResource(
            $instance
        );
    }

    /**
     * @param  CreateRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(CreateRequest $request)
    {
        $instance = new Instance($request->only([
            'name',
            'vpc_id',
            'availability_zone_id',
            'vcpu_cores',
            'ram_capacity',
            'locked'
        ]));
        if (!$request->has('locked')) {
            $instance->locked = false;
        }
        if ($request->has('appliance_id')) {
            $instance->setApplianceVersionId($request->get('appliance_id'));
        }
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
        if (!$this->isAdmin &&
            (!$request->has('locked') || $request->get('locked') !== false) &&
            $instance->locked === true) {
            return $this->isLocked();
        }
        $instance->fill($request->only([
            'name',
            'vpc_id',
            'availability_zone_id',
            'vcpu_cores',
            'ram_capacity',
            'locked'
        ]));
        if ($request->has('appliance_id')) {
            $instance->setApplianceVersionId($request->get('appliance_id'));
        }
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

    public function volumes(Request $request, string $instanceId)
    {
        return VolumeResource::collection(
            Instance::forUser($request->user)
                ->findOrFail($instanceId)
                ->volumes()
                ->paginate($request->input('per_page', env('PAGINATION_LIMIT')))
        );
    }

    public function deploy(DeployRequest $request, string $instanceId)
    {
        $instance = Instance::forUser(app('request')->user)->findOrFail($instanceId);
        if (!$instance) {
            return response()->json([], 404);
        }

        // Use the default network if there is only one
        $defaultNetwork = null;
        if (!$request->has('network_id')) {
            $routers = $instance->vpc->routers;
            if (count($routers) == 1) {
                $networks = $routers->first()->networks;
                if (count($networks) == 1) {
                    $defaultNetwork = $networks->first();
                }
            }
        }

        $data = [
            'instance_id' => $instance->id,
            'vpc_id' => $instance->vpc->id,
            'volume_capacity' => $request->input('volume_capacity', config('volume.capacity.min')),
            'network_id' => $request->input('network_id', $defaultNetwork),
            'floating_ip_id' => $request->input('floating_ip_id'),
            'appliance_data' => $request->input('appliance_data'),
        ];

        // Create the chained jobs for deployment
        $this->dispatch((new \App\Jobs\Instance\Deploy\Deploy($data))->chain([
            new \App\Jobs\Instance\Deploy\UpdateNetworkAdapter($data),
            new \App\Jobs\Instance\Deploy\OsCustomisation($data),
            new \App\Jobs\Instance\PowerOn($data),
            new \App\Jobs\Instance\Deploy\WaitOsCustomisation($data),
            new \App\Jobs\Instance\Deploy\PrepareOsUsers($data),
            new \App\Jobs\Instance\Deploy\PrepareOsDisk($data),
            new \App\Jobs\Instance\Deploy\RunApplianceBootstrap($data),
            new \App\Jobs\Instance\Deploy\RunBootstrapScript($data),
        ]));

        return response()->json([], 202);
    }

    public function powerOn(Request $request, $instanceId)
    {
        $instance = Instance::forUser($request->user)
            ->findOrFail($instanceId);

        // @todo - trigger power-onff event

        return response()->json([], 202);
    }

    public function powerOff(Request $request, $instanceId)
    {
        $instance = Instance::forUser($request->user)
            ->findOrFail($instanceId);

        // @todo - trigger power-off event

        return response()->json([], 202);
    }
}
