<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\CreateVolumeRequest;
use App\Http\Requests\V2\UpdateVolumeRequest;
use App\Models\V2\Volume;
use App\Resources\V2\VolumeResource;
use Illuminate\Http\Request;
use UKFast\DB\Ditto\QueryTransformer;

/**
 * Class VolumeController
 * @package App\Http\Controllers\V2
 */
class VolumeController extends BaseController
{
    /**
     * Get volumes collection
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
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
     * @param \Illuminate\Http\Request $request
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
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(CreateVolumeRequest $request)
    {
        $router = new Volume($request->only(['name', 'vpc_id', 'capacity']));
        $router->save();
        $router->refresh();
        return $this->responseIdMeta($request, $router->getKey(), 201);
    }

    /**
     * @param UpdateVolumeRequest $request
     * @param string $volumeId
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateVolumeRequest $request, string $volumeId)
    {
        $router = Volume::forUser(app('request')->user)->findOrFail($volumeId);
        $only = ['name', 'vpc_id', 'capacity'];
        if ($this->isAdmin) {
            $only[] = 'vmware_uuid';
        }
        $router->fill($request->only($only));
        $router->save();
        return $this->responseIdMeta($request, $router->getKey(), 200);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param string $routerUuid
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, string $volumeId)
    {
        Volume::forUser($request->user)->findOrFail($volumeId)->delete();
        return response()->json([], 204);
    }
}
