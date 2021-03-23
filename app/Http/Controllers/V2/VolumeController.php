<?php

namespace App\Http\Controllers\V2;

use App\Exceptions\SyncException;
use App\Http\Requests\V2\Volume\AttachRequest;
use App\Http\Requests\V2\Volume\DetachRequest;
use App\Http\Requests\V2\Volume\CreateRequest;
use App\Http\Requests\V2\Volume\UpdateRequest;
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
                return response()->json([
                    'errors' => [
                        'title' => 'Not Found',
                        'detail' => 'The specified availability zone is not available to that VPC',
                        'status' => 404,
                        'source' => 'availability_zone_id'
                    ]
                ], 404);
            }
        }

        $model = app()->make(Volume::class);
        $model->fill($request->only([
            'name',
            'vpc_id',
            'availability_zone_id',
            'capacity',
            'iops',
        ]));
        if (!$model->save()) {
            return $model->getSyncError();
        }
        return $this->responseIdMeta($request, $model->id, 201);
    }

    public function update(UpdateRequest $request, string $volumeId)
    {
        $volume = Volume::forUser(Auth::user())->findOrFail($volumeId);
        $only = ['name', 'capacity', 'iops'];
        if ($this->isAdmin) {
            $only[] = 'vmware_uuid';
        }
        $volume->fill($request->only($only));
        try {
            if (!$volume->save()) {
                return $volume->getSyncError();
            }
        } catch (SyncException $exception) {
            return $volume->getSyncError();
        }

        return $this->responseIdMeta($request, $volume->id, 200);
    }

    public function destroy(Request $request, string $volumeId)
    {
        $volume = Volume::forUser($request->user())->findOrFail($volumeId);
        try {
            $volume->delete();
        } catch (SyncException $exception) {
            return $volume->getSyncError();
        }
        return response('', 204);
    }

    public function attach(AttachRequest $request, string $volumeId)
    {
        $model = Volume::forUser(Auth::user())->findOrFail($volumeId);
        $instance = Instance::forUser(Auth::user())->findOrFail($request->get('instance_id'));
        try {
            $instance->volumes()->attach($model);
        } catch (SyncException $exception) {
            return $model->getSyncError();
        }
        return response('', 202);
    }

    public function detach(DetachRequest $request, string $volumeId)
    {
        $model = Volume::forUser(Auth::user())->findOrFail($volumeId);
        $instance = Instance::forUser(Auth::user())->findOrFail($request->get('instance_id'));
        try {
            $instance->volumes()->detach($model);
        } catch (SyncException $exception) {
            return $model->getSyncError();
        }
        return response('', 202);
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
}
