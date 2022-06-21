<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\ResourceTier\Create;
use App\Models\V2\ResourceTier;
use App\Resources\V2\ResourceTierResource;
use Illuminate\Http\Request;

class ResourceTierController extends BaseController
{
    public function index(Request $request)
    {
        $collection = ResourceTier::all();

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
        return $this->responseIdMeta($request, $model->id, 202);
    }

    public function destroy(string $resourceTierId)
    {
        ResourceTier::findOrFail($resourceTierId)
            ->delete();
        return response('', 204);
    }
}
