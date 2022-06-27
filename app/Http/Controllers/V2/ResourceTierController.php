<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\ResourceTier\Create;
use App\Http\Requests\V2\ResourceTier\Update;
use App\Models\V2\ResourceTier;
use App\Resources\V2\HostGroupResource;
use App\Resources\V2\ResourceTierResource;
use Illuminate\Http\Request;

class ResourceTierController extends BaseController
{
    public function index(Request $request)
    {
        $collection = ResourceTier::query();

        return ResourceTierResource::collection(
            $collection->search()
                ->paginate(
                    $request->input('per_page', env('PAGINATION_LIMIT'))
                )
        );
    }

    public function show(string $resourceTierId)
    {
        return new ResourceTierResource(
            ResourceTier::findOrFail($resourceTierId)
        );
    }

    public function store(Create $request)
    {
        $model = app()->make(ResourceTier::class);
        $model->fill($request->only([
            'name',
            'availability_zone_id',
        ]));
        $model->save();
        return $this->responseIdMeta($request, $model->id, 201);
    }

    public function update(Update $request, $resourceTierId)
    {
        $model = ResourceTier::findOrFail($resourceTierId);
        $model->update($request->only([
            'name',
        ]));
        $model->save();
        return $this->responseIdMeta($request, $model->id, 200);
    }

    public function destroy(string $resourceTierId)
    {
        ResourceTier::findOrFail($resourceTierId)
            ->delete();
        return response('', 204);
    }

    public function hostGroups(Request $request, string $resourceTierId)
    {
        $collection = ResourceTier::findOrFail($resourceTierId)->hostGroups();

        return HostGroupResource::collection($collection->search()->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }
}
