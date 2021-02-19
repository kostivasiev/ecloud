<?php

namespace App\Http\Controllers\V2;

use App\Events\V2\Volume\Data;
use App\Http\Requests\V2\Volume\AttachRequest;
use App\Http\Requests\V2\Volume\CreateRequest;
use App\Http\Requests\V2\Volume\UpdateRequest;
use App\Jobs\Volume\AttachToInstance;
use App\Models\V2\Instance;
use App\Models\V2\Volume;
use App\Models\V2\Vpc;
use App\Resources\V2\InstanceResource;
use App\Resources\V2\VolumeResource;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use UKFast\DB\Ditto\QueryTransformer;

class VolumeController extends BaseController
{
    public function index(Request $request)
    {
        if ($request->hasAny([
            'mounted',
            'mounted:eq',
            'mounted:neq',
        ])) {
            if ($request->has('mounted') || $request->has('mounted:eq')) {
                if ($request->has('mounted')) {
                    $mounted = filter_var($request->get('mounted'), FILTER_VALIDATE_BOOLEAN);
                    $request->query->remove('mounted');
                } else {
                    $mounted = filter_var($request->get('mounted:eq'), FILTER_VALIDATE_BOOLEAN);
                    $request->query->remove('mounted:eq');
                }
            } elseif ($request->has('mounted:neq')) {
                $mounted = !filter_var($request->get('mounted:neq'), FILTER_VALIDATE_BOOLEAN);
                $request->query->remove('mounted:neq');
            }

            if ($mounted) {
                $collection = Volume::forUser($request->user())->has('instances', '>', 0);
            } else {
                $collection = Volume::forUser($request->user())->has('instances', '=', 0);
            }
        } else {
            $collection = Volume::forUser($request->user());
        }

        (new QueryTransformer($request))
            ->config(Volume::class)
            ->transform($collection);

        return VolumeResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    public function show(Request $request, string $volumeId)
    {
        return new VolumeResource(
            Volume::forUser($request->user())->findOrFail($volumeId)
        );
    }

    public function store(CreateRequest $request)
    {
        if ($request->has('availability_zone_id')) {
            $availabilityZone = Vpc::forUser(Auth::user())
                ->findOrFail($request->vpc_id)
                ->region
                ->availabilityZones
                ->first(function ($availabilityZone) use ($request) {
                    return $availabilityZone->id == $request->availability_zone_id;
                });

            if (!$availabilityZone) {
                return Response::create([
                    'errors' => [
                        'title' => 'Not Found',
                        'detail' => 'The specified availability zone is not available to that VPC',
                        'status' => 404,
                        'source' => 'availability_zone_id'
                    ]
                ], 404);
            }
        }

        $volume = new Volume($request->only(['name', 'vpc_id', 'availability_zone_id', 'capacity']));
        $volume->save();
        $volume->refresh();
        return $this->responseIdMeta($request, $volume->getKey(), 201);
    }

    public function update(UpdateRequest $request, string $volumeId)
    {
        $volume = Volume::forUser(Auth::user())->findOrFail($volumeId);
        if ($request->has('availability_zone_id')) {
            $availabilityZone = Vpc::forUser(Auth::user())
                ->findOrFail($request->input('vpc_id', $volume->vpc_id))
                ->region
                ->availabilityZones
                ->first(function ($availabilityZone) use ($request) {
                    return $availabilityZone->id == $request->availability_zone_id;
                });

            if (!$availabilityZone) {
                return Response::create([
                    'errors' => [
                        'title' => 'Not Found',
                        'detail' => 'The specified availability zone is not available to that VPC',
                        'status' => 404,
                        'source' => 'availability_zone_id'
                    ]
                ], 404);
            }
        }

        $only = ['name', 'vpc_id', 'capacity', 'availability_zone_id', 'iops'];
        if ($this->isAdmin) {
            $only[] = 'vmware_uuid';
        }
        $volume->fill($request->only($only));
        if (!$volume->save()) {
            return $volume->getSyncError();
        }

        return $this->responseIdMeta($request, $volume->getKey(), 200);
    }

    public function instances(Request $request, QueryTransformer $queryTransformer, string $volumeId)
    {
        $collection = Volume::forUser($request->user())->findOrFail($volumeId)->instances();
        $queryTransformer->config(Instance::class)
            ->transform($collection);

        return InstanceResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    public function destroy(Request $request, string $volumeId)
    {
        $volume = Volume::forUser($request->user())->findOrFail($volumeId);
        if (!$volume->delete()) {
            return $volume->getSyncError();
        }
        return response(null, 204);
    }

    public function attachToInstance(AttachRequest $request, string $volumeId)
    {
        $volume = Volume::forUser(Auth::user())->findOrFail($volumeId);
        $instance = Instance::forUser(Auth::user())->findOrFail($request->get('instance_id'));
        $instance->volumes()->attach($volume);
        $this->dispatch(new AttachToInstance($volume, $instance));
        return response('', 202);
    }
}
