<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\Instance\CreateImageRequest;
use App\Http\Requests\V2\Instance\CreateRequest;
use App\Http\Requests\V2\Instance\MigrateRequest;
use App\Http\Requests\V2\Instance\UpdateRequest;
use App\Http\Requests\V2\Instance\VolumeAttachRequest;
use App\Http\Requests\V2\Instance\VolumeDetachRequest;
use App\Jobs\Instance\PowerOn;
use App\Models\V2\Credential;
use App\Models\V2\Image;
use App\Models\V2\ImageMetadata;
use App\Models\V2\Instance;
use App\Models\V2\Network;
use App\Models\V2\Nic;
use App\Models\V2\Task;
use App\Models\V2\Volume;
use App\Models\V2\Vpc;
use App\Resources\V2\CredentialResource;
use App\Resources\V2\InstanceResource;
use App\Resources\V2\NicResource;
use App\Resources\V2\TaskResource;
use App\Resources\V2\VolumeResource;
use Carbon\Carbon;
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

        $image = Image::forUser(Auth::user())->findOrFail($request->input('image_id'));
        $network = Network::forUser(Auth::user())->findOrFail($request->input('network_id'));
        $instance->availabilityZone()->associate($network->router->availabilityZone);

        $instance->locked = $request->input('locked', false);
        $instance->deploy_data = [
            'volume_capacity' => $request->input('volume_capacity', config('volume.capacity.' . strtolower($image->platform) . '.min')),
            'volume_iops' => $request->input('volume_iops', config('volume.iops.default')),
            'network_id' => $request->input('network_id', $network->id),
            'floating_ip_id' => $request->input('floating_ip_id'),
            'requires_floating_ip' => $request->input('requires_floating_ip', false),
            'image_data' => $request->input('image_data'),
            'user_script' => $request->input('user_script'),
            'ssh_key_pair_ids' => $request->input('ssh_key_pair_ids'),
        ];

        $task = $instance->syncSave();
        return $this->responseIdMeta($request, $instance->id, 202, $task->id);
    }

    /**
     * @param UpdateRequest $request
     * @param string $instanceId
     * @return JsonResponse
     */
    public function update(UpdateRequest $request, string $instanceId)
    {
        $instance = Instance::forUser(Auth::user())->hidden()->findOrFail($instanceId);

        $instance->fill($request->only([
            'name',
            'vcpu_cores',
            'ram_capacity',
            'volume_group_id',
        ]));

        if ($request->has('backup_enabled') && $this->isAdmin) {
            $instance->backup_enabled = $request->input('backup_enabled', $instance->backup_enabled);
        }

        $task = $instance->syncSave();
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
            $this->dispatch(new PowerOn($instance));
        });

        $task = $instance->createTaskWithLock('power_on', \App\Jobs\Tasks\Instance\PowerOn::class);

        return $this->responseTaskId($task->id);
    }

    public function powerOff(Request $request, $instanceId)
    {
        $instance = Instance::forUser($request->user())
            ->findOrFail($instanceId);

        $task = $instance->createTaskWithLock('power_off', \App\Jobs\Tasks\Instance\PowerOff::class);

        return $this->responseTaskId($task->id);
    }

    public function guestRestart(Request $request, $instanceId)
    {
        $instance = Instance::forUser($request->user())
            ->findOrFail($instanceId);

        $task = $instance->createTaskWithLock('guest_restart', \App\Jobs\Tasks\Instance\GuestRestart::class);

        return $this->responseTaskId($task->id);
    }

    public function guestShutdown(Request $request, $instanceId)
    {
        $instance = Instance::forUser($request->user())
            ->findOrFail($instanceId);

        $task = $instance->createTaskWithLock('guest_shutdown', \App\Jobs\Tasks\Instance\GuestShutdown::class);

        return $this->responseTaskId($task->id);
    }

    public function powerReset(Request $request, $instanceId)
    {
        $instance = Instance::forUser($request->user())
            ->findOrFail($instanceId);

        $task = $instance->createTaskWithLock('power_reset', \App\Jobs\Tasks\Instance\PowerReset::class);

        return $this->responseTaskId($task->id);
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

    public function createImage(CreateImageRequest $request, $instanceId)
    {
        $instance = Instance::forUser(Auth::user())->findOrFail($instanceId);

        if (!$instance->volumes()->count()) {
            return response()->json([
                'title' => 'Validation Error',
                'detail' => 'Cannot create an image of an Instance with no attached volumes',
                'status' => 422,
            ], 422);
        }

        $image = $instance->image->replicate(['vm_template', 'script_template', 'logo_uri', 'description'])
            ->fill($request->only([
                'name'
            ]));
        $image->visibility = Image::VISIBILITY_PRIVATE;
        $image->vpc_id = $instance->vpc_id;
        $image->description = $request->input(
            'description',
            "Image taken from instance $instance->id on " . Carbon::now(new \DateTimeZone(config('app.timezone')))->toDayDateTimeString()
        );

        $image->save();

        $instance->image->imageMetadata->each(function ($imageMetadata) use ($image) {
            $meta = $imageMetadata->replicate();
            $meta->image_id = $image->id;
            $meta->save();
        });

        $volumeCapacity = $instance->volumes->where('os_volume', '=', true)->first()->capacity;

        ImageMetadata::updateOrCreate(
            ['image_id' => $image->id, 'key' => 'ukfast.spec.volume.min'],
            ['image_id' => $image->id, 'key' => 'ukfast.spec.volume.min', 'value' => $volumeCapacity]
        );

        $image->availabilityZones()->sync($instance->availabilityZone);

        $task = $instance->createTaskWithLock(
            'image_create',
            \App\Jobs\Tasks\Instance\CreateImage::class,
            [
                'image_id' => $image->id
            ]
        );

        return response()->json(
            [
                'data' => [
                    'id' => $image->id,
                    'task_id' => $task->id
                ],
                'meta' => [
                    'location' => config('app.url') . 'v2/images/' . $image->id,
                    'task_location' => config('app.url') . 'v2/tasks/' . $task->id
                ],
            ],
            202
        );
    }

    public function migrate(MigrateRequest $request, $instanceId)
    {
        $instance = Instance::forUser(Auth::user())->findOrFail($instanceId);
        if ($request->has('host_group_id')) {
            $task = $instance->createTaskWithLock(
                'instance_migrate_private',
                \App\Jobs\Tasks\Instance\MigratePrivate::class,
                ['host_group_id' => $request->input('host_group_id')]
            );
        } else {
            $task = $instance->createTaskWithLock(
                'instance_migrate_public',
                \App\Jobs\Tasks\Instance\MigratePublic::class
            );
        }

        return $this->responseTaskId($task->id);
    }
}
