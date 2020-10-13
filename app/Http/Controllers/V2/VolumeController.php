<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\CreateVolumeRequest;
use App\Http\Requests\V2\UpdateVolumeRequest;
use App\Models\V2\Volume;
use App\Models\V2\Vpc;
use App\Resources\V2\InstanceResource;
use App\Resources\V2\VolumeResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use UKFast\DB\Ditto\QueryTransformer;

/**
 * Class VolumeController
 * @package App\Http\Controllers\V2
 */
class VolumeController extends BaseController
{
    /**
     * Get volumes collection
     * @param Request $request
     * @return Response
     */
    public function index(Request $request)
    {
        $collection = Volume::forUser($request->user);

        (new QueryTransformer($request))
            ->config(Volume::class)
            ->transform($collection);

        return VolumeResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    /**
     * @param Request $request
     * @param string $volumeId
     * @return VolumeResource
     */
    public function show(Request $request, string $volumeId)
    {
        return new VolumeResource(
            Volume::forUser($request->user)->findOrFail($volumeId)
        );
    }

    /**
     * @param CreateVolumeRequest $request
     * @return JsonResponse|Response
     */
    public function store(CreateVolumeRequest $request)
    {
        if ($request->has('availability_zone_id')) {
            $availabilityZone = Vpc::forUser(app('request')->user)
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

    /**
     * @param UpdateVolumeRequest $request
     * @param string $volumeId
     * @return JsonResponse|Response
     */
    public function update(UpdateVolumeRequest $request, string $volumeId)
    {
        $volume = Volume::forUser(app('request')->user)->findOrFail($volumeId);
        if ($request->has('availability_zone_id')) {
            $availabilityZone = Vpc::forUser(app('request')->user)
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

        $only = ['name', 'vpc_id', 'capacity', 'availability_zone_id'];
        if ($this->isAdmin) {
            $only[] = 'vmware_uuid';
        }
        $volume->fill($request->only($only));
        $volume->save();
        return $this->responseIdMeta($request, $volume->getKey(), 200);
    }

    public function instances(Request $request, string $volumeId)
    {
        return InstanceResource::collection(
            Volume::forUser($request->user)
                ->findOrFail($volumeId)
                ->instances()
                ->paginate($request->input('per_page', env('PAGINATION_LIMIT')))
        );
    }

    /**
     * @param Request $request
     * @param string $routerUuid
     * @return JsonResponse
     */
    public function destroy(Request $request, string $volumeId)
    {
        Volume::forUser($request->user)->findOrFail($volumeId)->delete();
        return response()->json([], 204);
    }
}
