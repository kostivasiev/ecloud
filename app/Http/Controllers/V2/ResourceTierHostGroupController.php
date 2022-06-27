<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\ResourceTierHostGroup\Create;
use App\Models\V2\ResourceTierHostGroup;
use App\Resources\V2\ResourceTierHostGroupResource;
use Illuminate\Http\Request;

class ResourceTierHostGroupController extends BaseController
{
    public function index(Request $request)
    {
        $collection = ResourceTierHostGroup::query();

        return ResourceTierHostGroupResource::collection(
            $collection->search()
                ->paginate(
                    $request->input('per_page', env('PAGINATION_LIMIT'))
                )
        );
    }

    public function show(string $resourceTierHostGroupId)
    {
        return new ResourceTierHostGroupResource(
            ResourceTierHostGroup::findOrFail($resourceTierHostGroupId)
        );
    }

    public function store(Create $request)
    {
        $model = app()->make(ResourceTierHostGroup::class);
        $model->fill($request->only([
            'resource_tier_id',
            'host_group_id',
        ]));

        $model->save();
        return $this->responseIdMeta($request, $model->id, 201);
    }

    public function destroy(Request $request, string $resourceTierHostGroupId)
    {
        $model = ResourceTierHostGroup::findOrFail($resourceTierHostGroupId);
        $model->delete();
        return response('', 204);
    }
}
