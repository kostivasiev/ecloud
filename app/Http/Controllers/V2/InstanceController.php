<?php

namespace App\Http\Controllers\V2;

use App\Resources\V2\VolumeResource;
use Illuminate\Http\Request;
use App\Events\V2\Data\InstanceDeployEventData;
use App\Events\V2\InstanceDeployEvent;
use App\Http\Requests\V2\Instance\CreateRequest;
use App\Http\Requests\V2\Instance\DeployRequest;
use App\Http\Requests\V2\Instance\UpdateRequest;
use App\Models\V2\Instance;
use App\Models\V2\Network;
use App\Resources\V2\CredentialResource;
use App\Resources\V2\InstanceResource;
use App\Resources\V2\NicResource;
use App\Resources\V2\VolumeResource;
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
     * @param Request $request
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

        $instanceDeployData = new InstanceDeployEventData();
        $instanceDeployData->instance_id = $instance->id;
        $instanceDeployData->vpc_id = $instance->vpc->id;
        $instanceDeployData->volume_capacity = $request->input('volume_capacity', config('volume.capacity.min'));
        $instanceDeployData->network_id = $request->input('network_id', $defaultNetworkId);
        $instanceDeployData->floating_ip_id = $request->input('floating_ip_id');
        $instanceDeployData->appliance_data = $request->input('appliance_data');
        $instanceDeployData->user_script = $request->input('user_script');
        event(new InstanceDeployEvent($instanceDeployData));

        return $this->responseIdMeta($request, $instance->getKey(), 201);
    }

    /**
     * @param UpdateRequest $request
     * @param string $instanceId
     * @return JsonResponse
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
     * @return \Illuminate\Http\Response
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

    /**
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $instanceId
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

        $this->dispatch(new \App\Jobs\Instance\PowerOn([
            'instance_id' => $instance->id,
            'vpc_id' => $instance->vpc->id
        ]));

        return response('', 202);
    }

    public function powerOff(Request $request, $instanceId)
    {
        $instance = Instance::forUser($request->user)
            ->findOrFail($instanceId);

        $this->dispatch(new \App\Jobs\Instance\PowerOff([
            'instance_id' => $instance->id,
            'vpc_id' => $instance->vpc->id
        ]));


        return response('', 202);
    }

    public function guestRestart(Request $request, $instanceId)
    {
        $instance = Instance::forUser($request->user)
            ->findOrFail($instanceId);

        $this->dispatch(new \App\Jobs\Instance\GuestRestart([
            'instance_id' => $instance->id,
            'vpc_id' => $instance->vpc->id
        ]));

        return response('', 202);
    }

    public function guestShutdown(Request $request, $instanceId)
    {
        $instance = Instance::forUser($request->user)
            ->findOrFail($instanceId);

        $this->dispatch(new \App\Jobs\Instance\GuestShutdown([
            'instance_id' => $instance->id,
            'vpc_id' => $instance->vpc->id
        ]));

        return response('', 202);
    }

    public function powerReset(Request $request, $instanceId)
    {
        $instance = Instance::forUser($request->user)
            ->findOrFail($instanceId);

        $this->dispatch(new \App\Jobs\Instance\PowerReset([
            'instance_id' => $instance->id,
            'vpc_id' => $instance->vpc->id
        ]));

        return response('', 202);
    }
}
