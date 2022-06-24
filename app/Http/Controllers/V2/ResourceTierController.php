<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\ResourceTier\Create;
use App\Http\Requests\V2\ResourceTier\Update;
use App\Models\V2\ResourceTier;
use App\Resources\V2\ResourceTierResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ResourceTierController extends BaseController
{
    public function index(Request $request)
    {
        $collection = ResourceTier::forUser($request->user());

        return ResourceTierResource::collection(
            $collection->search()
                ->paginate(
                    $request->input('per_page', env('PAGINATION_LIMIT'))
                )
        );
    }

    public function show(Request $request, string $resourceTierId)
    {
        return new ResourceTierResource(
            ResourceTier::forUser($request->user())->findOrFail($resourceTierId)
        );
    }

    public function store(Create $request)
    {
        $model = app()->make(ResourceTier::class);
        $model->fill($request->only([
            'name',
            'availability_zone_id',
            'active'
        ]));
        $model->save();
        return $this->responseIdMeta($request, $model->id, 201);
    }

    public function update(Update $request, $resourceTierId)
    {
        $model = ResourceTier::forUser(Auth::user())->findOrFail($resourceTierId);
        $model->update($request->only([
            'name',
            'active',
        ]));
        $model->save();
        return $this->responseIdMeta($request, $model->id, 200);
    }

    public function destroy(string $resourceTierId)
    {
        ResourceTier::forUser(Auth::user())->findOrFail($resourceTierId)
            ->delete();
        return response('', 204);
    }
}
