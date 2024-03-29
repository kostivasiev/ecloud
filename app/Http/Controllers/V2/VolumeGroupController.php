<?php
namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\VolumeGroup\CreateRequest;
use App\Http\Requests\V2\VolumeGroup\UpdateRequest;
use App\Models\V2\Volume;
use App\Models\V2\VolumeGroup;
use App\Resources\V2\VolumeGroupResource;
use App\Resources\V2\VolumeResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use UKFast\DB\Ditto\QueryTransformer;

class VolumeGroupController extends BaseController
{
    public function index(Request $request)
    {
        $collection = VolumeGroup::forUser($request->user());

        return VolumeGroupResource::collection($collection->search()->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    public function show(Request $request, string $volumeGroupId)
    {
        return new VolumeGroupResource(
            VolumeGroup::forUser($request->user())->findOrFail($volumeGroupId)
        );
    }

    public function store(CreateRequest $request)
    {
        $volumeGroup = new VolumeGroup($request->only(['name', 'vpc_id', 'availability_zone_id']));

        $task = $volumeGroup->syncSave();
        return $this->responseIdMeta($request, $volumeGroup->id, 202, $task->id);
    }

    public function update(UpdateRequest $request, string $volumeGroupId)
    {
        $volumeGroup = VolumeGroup::forUser(Auth::user())->findOrFail($volumeGroupId);
        $volumeGroup->fill($request->only(['name']));

        $task = $volumeGroup->syncSave();
        return $this->responseIdMeta($request, $volumeGroup->id, 202, $task->id);
    }

    public function destroy(Request $request, string $volumeGroupId)
    {
        $volumeGroup = VolumeGroup::forUser($request->user())->findOrFail($volumeGroupId);

        $task = $volumeGroup->syncDelete();
        return $this->responseTaskId($task->id);
    }

    public function volumes(Request $request, string $volumeGroupId)
    {
        $collection = VolumeGroup::forUser($request->user())
            ->findOrFail($volumeGroupId)
            ->volumes()
            ->forUser($request->user());

        return VolumeResource::collection($collection->search()->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }
}
