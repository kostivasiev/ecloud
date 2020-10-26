<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\Instance\CreateRequest;
use App\Http\Requests\V2\Instance\UpdateRequest;
use App\Jobs\Instance\GuestRestart;
use App\Jobs\Instance\GuestShutdown;
use App\Jobs\Instance\PowerOff;
use App\Jobs\Instance\PowerOn;
use App\Jobs\Instance\PowerReset;
use App\Models\V2\Instance;
use App\Models\V2\Network;
use App\Resources\V2\CredentialResource;
use App\Resources\V2\InstanceResource;
use App\Resources\V2\VolumeResource;
use App\Resources\V2\NicResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\HigherOrderTapProxy;
use UKFast\DB\Ditto\QueryTransformer;

/**
 * Class InstanceController
 * @package App\Http\Controllers\V2
 */
class InstanceController extends BaseController
{
    /**
     * Get instance collection
     * @param Request $request
     * @param QueryTransformer $queryTransformer
     * @return Response
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
     * @param Request $request
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
     * @param CreateRequest $request
     * @return JsonResponse
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

        // Use the default network if there is only one and no network_id was passed in
        $defaultNetworkId = null;
        if (!$request->has('network_id')) {
            $routers = $instance->vpc->routers;
            if (count($routers) == 1) {
                $networks = $routers->first()->networks;
                if (count($networks) == 1) {
                    // This could be done better, but deadlines. Should check all routers/networks for owned Networks
                    $defaultNetworkId = Network::forUser(app('request')->user)->findOrFail($networks->first()->id)->id;
                }
            }
            if (!$defaultNetworkId) {
                return JsonResponse::create([
                    'errors' => [
                        'title' => 'Not Found',
                        'detail' => 'No network_id provided and could not find a default network',
                        'status' => 404,
                        'source' => 'availability_zone_id'
                    ]
                ], 404);
            }
        }

        $instanceDeployData = new \App\Events\V2\Instance\Deploy\Data();
        $instanceDeployData->instance_id = $instance->id;
        $instanceDeployData->vpc_id = $instance->vpc->id;
        $instanceDeployData->volume_capacity = $request->input('volume_capacity', config('volume.capacity.min'));
        $instanceDeployData->network_id = $request->input('network_id', $defaultNetworkId);
        $instanceDeployData->floating_ip_id = $request->input('floating_ip_id');
        $instanceDeployData->requires_floating_ip = $request->input('requires_floating_ip', false);
        $instanceDeployData->appliance_data = $request->input('appliance_data');
        $instanceDeployData->user_script = $request->input('user_script');
        event(new \App\Events\V2\Instance\Deploy($instanceDeployData));

        return $this->responseIdMeta($request, $instance->getKey(), 201);
    }

    /**
     * @param UpdateRequest $request
     * @param string $instanceId
     * @return JsonResponse
     */
    public function update(UpdateRequest $request, string $instanceId)
    {
        $rebootRequired = false;
        $instance = Instance::forUser(app('request')->user)->findOrFail($instanceId);
        if (!$this->isAdmin &&
            (!$request->has('locked') || $request->get('locked') !== false) &&
            $instance->locked === true) {
            return $this->isLocked();
        }
        if (($request->input('vcpu_cores', $instance->vcpu_cores) < $instance->vcpu_cores) ||
            ($request->input('ram_capacity', $instance->ram_capacity) < $instance->ram_capacity)) {
            $rebootRequired = true;
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
        if ($request->has('vcpu_cores') || $request->has('ram_capacity')) {
            event(new \App\Events\V2\Instance\ComputeChanged($instance, $rebootRequired));
        }
        return $this->responseIdMeta($request, $instance->getKey(), 200);
    }

    /**
     * @return JsonResponse
     */
    private function isLocked(): JsonResponse
    {
        return JsonResponse::create([
            'errors' => [
                'title' => 'Forbidden',
                'detail' => 'The specified instance is locked',
                'status' => 403,
            ]
        ], 403);
    }

    /**
     * @param Request $request
     * @param string $instanceId
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, string $instanceId)
    {
        $instance = Instance::forUser($request->user)->findOrFail($instanceId);
        if (!$this->isAdmin && $instance->locked === true) {
            return $this->isLocked();
        }
        $instance->delete();
        return response('', 204);
    }

    /**
     * @param Request $request
     * @param string $instanceId
     *
     * @return AnonymousResourceCollection|HigherOrderTapProxy|mixed
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

    /**
     * @param \Illuminate\Http\Request $request
     * @param string $instanceId
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|\Illuminate\Support\HigherOrderTapProxy|mixed
     */
    public function nics(Request $request, string $instanceId)
    {
        return NicResource::collection(
            Instance::forUser($request->user)
                ->findOrFail($instanceId)
                ->nics()
                ->paginate($request->input('per_page', env('PAGINATION_LIMIT')))
        );
    }

    public function powerOn(Request $request, $instanceId)
    {
        $instance = Instance::forUser($request->user)
            ->findOrFail($instanceId);

        $this->dispatch(new PowerOn([
            'instance_id' => $instance->id,
            'vpc_id' => $instance->vpc->id
        ]));

        return response('', 202);
    }

    public function powerOff(Request $request, $instanceId)
    {
        $instance = Instance::forUser($request->user)
            ->findOrFail($instanceId);

        $this->dispatch(new PowerOff([
            'instance_id' => $instance->id,
            'vpc_id' => $instance->vpc->id
        ]));


        return response('', 202);
    }

    public function guestRestart(Request $request, $instanceId)
    {
        $instance = Instance::forUser($request->user)
            ->findOrFail($instanceId);

        $this->dispatch(new GuestRestart([
            'instance_id' => $instance->id,
            'vpc_id' => $instance->vpc->id
        ]));

        return response('', 202);
    }

    public function guestShutdown(Request $request, $instanceId)
    {
        $instance = Instance::forUser($request->user)
            ->findOrFail($instanceId);

        $this->dispatch(new GuestShutdown([
            'instance_id' => $instance->id,
            'vpc_id' => $instance->vpc->id
        ]));

        return response('', 202);
    }

    public function powerReset(Request $request, $instanceId)
    {
        $instance = Instance::forUser($request->user)
            ->findOrFail($instanceId);

        $this->dispatch(new PowerReset([
            'instance_id' => $instance->id,
            'vpc_id' => $instance->vpc->id
        ]));

        return response('', 202);
    }
}
