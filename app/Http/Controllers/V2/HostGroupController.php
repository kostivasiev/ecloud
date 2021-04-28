<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\HostGroup\StoreRequest;
use App\Http\Requests\V2\HostGroup\UpdateRequest;
use App\Models\V2\HostGroup;
use App\Resources\V2\HostGroupResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use UKFast\DB\Ditto\QueryTransformer;

class HostGroupController extends BaseController
{
    public function index(Request $request, QueryTransformer $queryTransformer)
    {
        $collection = HostGroup::forUser($request->user());

        $queryTransformer->config(HostGroup::class)
            ->transform($collection);

        return HostGroupResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    public function show(Request $request, string $id)
    {
        return new HostGroupResource(
            HostGroup::forUser($request->user())->findOrFail($id)
        );
    }

    public function store(StoreRequest $request)
    {
        $model = app()->make(HostGroup::class);
        $model->fill($request->only([
            'name',
            'vpc_id',
            'availability_zone_id',
            'host_spec_id',
            'windows_enabled',
        ]));
        $model->save();
        return $this->responseIdMeta($request, $model->id, 202);
    }

    public function update(UpdateRequest $request, string $id)
    {
        $model = HostGroup::forUser(Auth::user())->findOrFail($id);
        $model->fill($request->only([
            'name',
        ]));

        $model->withSyncLock(function ($model) {
            $model->save();
        });
        return $this->responseIdMeta($request, $model->id, 202);
    }

    public function destroy(Request $request, string $id)
    {
        $model = HostGroup::forUser($request->user())->findOrFail($id);

        if ($model->hosts()->count()) {
            return response()->json([
                'title' => 'Validation Error',
                'detail' => 'Can not delete Host group with active hosts',
                'status' => 422,
            ], 422);
        }

        $model->withSyncLock(function ($model) {
            $model->delete();
        });
        return response('', 204);
    }
}
