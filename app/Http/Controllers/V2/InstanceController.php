<?php

namespace App\Http\Controllers\V2;

use App\Exceptions\V2\TaskException;
use App\Http\Requests\V2\Instance\CreateRequest;
use App\Http\Requests\V2\Instance\HostGroupRequest;
use App\Http\Requests\V2\Instance\UpdateRequest;
use App\Http\Requests\V2\Instance\VolumeDetachRequest;
use App\Http\Requests\V2\Instance\VolumeAttachRequest;
use App\Jobs\Instance\GuestRestart;
use App\Jobs\Instance\GuestShutdown;
use App\Jobs\Instance\PowerOff;
use App\Jobs\Instance\PowerOn;
use App\Jobs\Instance\PowerReset;
use App\Models\V2\Credential;
use App\Models\V2\HostGroup;
use App\Models\V2\Instance;
use App\Models\V2\Nic;
use App\Models\V2\Task;
use App\Models\V2\Volume;
use App\Models\V2\Vpc;
use App\Resources\V2\CredentialResource;
use App\Resources\V2\InstanceResource;
use App\Resources\V2\NicResource;
use App\Resources\V2\TaskResource;
use App\Resources\V2\VolumeResource;
use App\Support\Sync;
use GuzzleHttp\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HigherOrderTapProxy;
use Illuminate\Validation\ValidationException;
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
        $collection = Instance::forUser($request->user());

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
        $instance = Instance::forUser($request->user())->findOrFail($instanceId);

        return new InstanceResource(
            $instance
        );
    }

    /**
     * @param CreateRequest $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function store(CreateRequest $request)
    {
        $vpc = Vpc::forUser(Auth::user())->findOrFail($request->input('vpc_id'));

        // Use the default network if there is only one and no network_id was passed in
        $defaultNetworkId = null;
        if (!$request->has('network_id')) {
            if ($vpc->routers->count() == 1 && $vpc->routers->first()->networks->count() == 1 && $vpc->routers->first()->sync->status !== Sync::STATUS_FAILED) {
                $defaultNetworkId = $vpc->routers->first()->networks->first()->id;
            }
            if (!$defaultNetworkId) {
                return response()->json([
                    'errors' => [
                        [
                            'title' => 'Not Found',
                            'detail' => 'No network_id provided and could not find a default network',
                            'status' => 404,
                            'source' => 'network_id'
                        ]
                    ]
                ], 404);
            }
        }

        $instance = new Instance($request->only([
            'name',
            'vpc_id',
            'image_id',
            'vcpu_cores',
            'ram_capacity',
            'locked',
            'backup_enabled',
            'host_group_id',
        ]));

        $instance->locked = $request->input('locked', false);
        $instance->deploy_data = [
            'volume_capacity' => $request->input('volume_capacity', config('volume.capacity.' . strtolower($instance->platform) . '.min')),
            'volume_iops' => $request->input('volume_iops', config('volume.iops.default')),
            'network_id' => $request->input('network_id', $defaultNetworkId),
            'floating_ip_id' => $request->input('floating_ip_id'),
            'requires_floating_ip' => $request->input('requires_floating_ip', false),
            'image_data' => $request->input('image_data'),
            'user_script' => $request->input('user_script'),
            'ssh_key_pair_ids' => $request->input('ssh_key_pair_ids'),
        ];

        $instance->save();

        return $this->responseIdMeta($request, $instance->id, 202);
    }

    /**
     * @param UpdateRequest $request
     * @param string $instanceId
     * @return JsonResponse
     */
    public function update(UpdateRequest $request, string $instanceId)
    {
        $instance = Instance::forUser(Auth::user())->findOrFail($instanceId);

        $instance->fill($request->only([
            'name',
            'vcpu_cores',
            'ram_capacity',
        ]));

        if ($request->has('backup_enabled') && $this->isAdmin) {
            $instance->backup_enabled = $request->input('backup_enabled', $instance->backup_enabled);
        }
        $instance->save();
        return $this->responseIdMeta($request, $instance->id, 202, $task->id);
    }

    /**
     * @param Request $request
     * @param string $instanceId
     * @return Response|JsonResponse
     */
    public function destroy(Request $request, string $instanceId)
    {
        $instance = Instance::forUser($request->user())->findOrFail($instanceId);

        $task = $instance->syncDelete();

        return $this->responseTaskId($task->id);
    }

    /**
     * @param Request $request
     * @param QueryTransformer $queryTransformer
     * @param string $instanceId
     *
     * @return AnonymousResourceCollection|HigherOrderTapProxy|mixed
     */
    public function credentials(Request $request, QueryTransformer $queryTransformer, string $instanceId)
    {
        $instance = Instance::forUser($request->user())->findOrFail($instanceId);
        if (!$instance->deployed && !$request->user()->isAdmin()) {
            return response()->json([
                'errors' => [
                    [
                        'title' => 'Not Found',
                        'detail' => 'Credentials will be available when instance deployment is complete',
                        'status' => 404,
                    ]
                ]
            ], 404);
        }
        $collection = $instance->credentials();
        if (!$request->user()->isAdmin()) {
            $collection->where('credentials.is_hidden', 0);
        }
        $queryTransformer->config(Credential::class)
            ->transform($collection);

        return CredentialResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    /**
     * @param Request $request
     * @param QueryTransformer $queryTransformer
     * @param string $instanceId
     * @return AnonymousResourceCollection|HigherOrderTapProxy|mixed
     */
    public function volumes(Request $request, QueryTransformer $queryTransformer, string $instanceId)
    {
        $collection = Instance::forUser($request->user())->findOrFail($instanceId)->volumes();
        $queryTransformer->config(Volume::class)
            ->transform($collection);

        return VolumeResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    /**
     * @param Request $request
     * @param QueryTransformer $queryTransformer
     * @param string $instanceId
     * @return AnonymousResourceCollection|HigherOrderTapProxy|mixed
     */
    public function nics(Request $request, QueryTransformer $queryTransformer, string $instanceId)
    {
        $collection = Instance::forUser($request->user())->findOrFail($instanceId)->nics();
        $queryTransformer->config(Nic::class)
            ->transform($collection);

        return NicResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    public function powerOn(Request $request, $instanceId)
    {
        $instance = Instance::forUser($request->user())
            ->findOrFail($instanceId);

        $instance->withTaskLock(function ($instance) {
            if (!$instance->canCreateTask()) {
                throw new TaskException();
            }
            $this->dispatch(new PowerOn($instance));
        });

        return response('', 202);
    }

    public function powerOff(Request $request, $instanceId)
    {
        $instance = Instance::forUser($request->user())
            ->findOrFail($instanceId);

        $instance->withTaskLock(function ($instance) {
            if (!$instance->canCreateTask()) {
                throw new TaskException();
            }
            $this->dispatch(new PowerOff($instance));
        });

        return response('', 202);
    }

    public function guestRestart(Request $request, $instanceId)
    {
        $instance = Instance::forUser($request->user())
            ->findOrFail($instanceId);

        $instance->withTaskLock(function ($instance) {
            if (!$instance->canCreateTask()) {
                throw new TaskException();
            }
            $this->dispatch(new GuestRestart($instance));
        });

        return response('', 202);
    }

    public function guestShutdown(Request $request, $instanceId)
    {
        $instance = Instance::forUser($request->user())
            ->findOrFail($instanceId);

        $instance->withTaskLock(function ($instance) {
            if (!$instance->canCreateTask()) {
                throw new TaskException();
            }
            $this->dispatch(new GuestShutdown($instance));
        });

        return response('', 202);
    }

    public function powerReset(Request $request, $instanceId)
    {
        $instance = Instance::forUser($request->user())
            ->findOrFail($instanceId);

        $instance->withTaskLock(function ($instance) {
            if (!$instance->canCreateTask()) {
                throw new TaskException();
            }
            $this->dispatch(new PowerReset($instance));
        });

        return response('', 202);
    }

    public function lock(Request $request, $instanceId)
    {
        $instance = Instance::forUser($request->user())->findOrFail($instanceId);
        $instance->locked = true;
        $instance->save();

        return response('', 204);
    }

    public function unlock(Request $request, $instanceId)
    {
        $instance = Instance::forUser($request->user())->findOrFail($instanceId);
        $instance->locked = false;
        $instance->save();

        return response('', 204);
    }

    public function consoleSession(Request $request, $instanceId)
    {
        $instance = Instance::forUser($request->user())->findOrFail($instanceId);

        if (!$instance->vpc->console_enabled) {
            if (!$this->isAdmin) {
                return response()->json([
                    'errors' => [
                        'title' => 'Forbidden',
                        'details' => 'Console access has been disabled for this resource',
                        'status' => Response::HTTP_FORBIDDEN,
                    ]
                ], Response::HTTP_FORBIDDEN);
            }
        }

        /** @var \GuzzleHttp\Psr7\Response $response */
        $response = $instance->availabilityZone
            ->kingpinService()
            ->post(
                '/api/v2/vpc/'.$instance->vpc_id.'/instance/'.$instance->id.'/console/session'
            );
        if (!$response || $response->getStatusCode() !== 200) {
            Log::info(
                __CLASS__ . ':: ' . __FUNCTION__ . ' : Failed to retrieve console session',
                [
                    'instance' => $instance,
                    'response' => $response,
                ]
            );
            return response()->json([
                'errors' => [
                    'title' => 'Bad Gateway',
                    'details' => 'Console access to this instance is not available',
                    'status' => Response::HTTP_BAD_GATEWAY,
                ]
            ], Response::HTTP_BAD_GATEWAY);
        }
        $json = json_decode($response->getBody()->getContents());
        $host = $json->host ?? null;
        $ticket = $json->ticket ?? null;

        // Get Credentials
        $consoleResource = $instance->availabilityZone->credentials()
            ->where('username', 'envoyapi')
            ->first();
        if (!$consoleResource) {
            Log::info(
                __CLASS__ . ':: ' . __FUNCTION__ . ' : Failed to retrieve console credentials',
                ['instance' => $instance]
            );
            return response()->json([
                'errors' => [
                    'title' => 'Upstream API Failure',
                    'details' => 'Console access is not available due to an upstream api failure',
                    'status' => Response::HTTP_SERVICE_UNAVAILABLE,
                ]
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        $message = $e = null;
        try {
            $client = app()->make(Client::class, [
                'config' => [
                    'base_uri' => $consoleResource->host.':'.$consoleResource->port,
                    'verify' => app()->environment() === 'production',
                    'headers' => [
                        'X-API-Authentication' => $consoleResource->password,
                    ],
                ],
            ]);
            $response = $client->post('/session', [
                \GuzzleHttp\RequestOptions::JSON => [
                    'host' => $host,
                    'ticket' => $ticket,
                ]
            ]);
        } catch (\Exception $e) {
            $response = response('', Response::HTTP_SERVICE_UNAVAILABLE);
            $message = $e->getMessage();
        }
        if (!$response || $response->getStatusCode() !== 201) {
            $message = $message ?? __CLASS__ . '::' . __FUNCTION__ . ' : Failed to retrieve session from host';
            Log::info(
                $message,
                [
                    'data' => [
                        'instance' => $instance,
                        'host' => $consoleResource->host,
                        'exception' => $e ?? '',
                    ]
                ]
            );
            return response()->json([
                'errors' => [
                    'title' => 'Upstream API Failure',
                    'details' => 'Console session is not available due to an upstream api failure',
                    'status' => Response::HTTP_SERVICE_UNAVAILABLE,
                ]
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        $responseJson = json_decode($response->getBody()->getContents());
        $uuid = $responseJson->uuid ?? '';
        if (empty($uuid)) {
            Log::info(__CLASS__ . '::' . __FUNCTION__ . ' : Failed to retrieve session UUID from host', [
                'instance' => $instance,
                'base_uri' => $consoleResource->host.':'.$consoleResource->port,
                'status_code' => Response::HTTP_SERVICE_UNAVAILABLE,
            ]);
            abort(Response::HTTP_SERVICE_UNAVAILABLE);
        }

        // respond to the Customer call with the URL containing the session UUID that allows them to connect to the console
        return response()->json([
            'data' => [
                'url' => $consoleResource->host . '/console/?title=' . $instance->id . '&session=' . $uuid,
            ],
            'meta' => (object)[]
        ]);
    }

    public function volumeAttach(VolumeAttachRequest $request, string $instanceId)
    {
        $instance = Instance::forUser(Auth::user())->findOrFail($instanceId);
        $volume = Volume::forUser(Auth::user())->findOrFail($request->get('volume_id'));

        $task = $instance->createTaskWithLock('volume_attach', \App\Jobs\Tasks\Instance\VolumeAttach::class, ['volume_id' => $volume->id]);

        return $this->responseTaskId($task->id);
    }

    public function volumeDetach(VolumeDetachRequest $request, string $instanceId)
    {
        $instance = Instance::forUser(Auth::user())->findOrFail($instanceId);
        $volume = Volume::forUser(Auth::user())->findOrFail($request->get('volume_id'));

        $task = $instance->createTaskWithLock('volume_detach', \App\Jobs\Tasks\Instance\VolumeDetach::class, ['volume_id' => $volume->id]);

        return $this->responseTaskId($task->id);
    }

    public function tasks(Request $request, QueryTransformer $queryTransformer, string $instanceId)
    {
        $collection = Instance::forUser($request->user())->findOrFail($instanceId)->tasks();
        $queryTransformer->config(Task::class)
            ->transform($collection);

        return TaskResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    public function createImage(Request $request, $instanceId)
    {

        // TODO - create an image from an instance
        return response('', 202);
    }

    public function hostGroup(HostGroupRequest $request, $instanceId)
    {
        $instance = Instance::forUser(Auth::user())->findOrFail($instanceId);
        $hostGroup = HostGroup::forUser(Auth::user())->findOrFail($request->get('host_group_id'));

        $task = $instance->createTaskWithLock(
            'instance_hostgroup',
            \App\Jobs\Tasks\Instance\HostGroupUpdate::class,
            ['host_group_id' => $hostGroup->id]
        );
        $instance->host_group_id = $hostGroup->id;
        $instance->saveQuietly();

        return $this->responseTaskId($task->id);
    }
}
