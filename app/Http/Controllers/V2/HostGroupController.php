<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\HostGroup\CreateRequest;
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

    public function store(CreateRequest $request)
    {
        $model = new HostGroup();
        $model->fill($request->only([
            'name',
            'vpc_id',
            'availability_zone_id',
            'host_spec_id',
        ]));
        $model->save();
        return $this->responseIdMeta($request, $model->id, 201);
    }

    public function update(UpdateRequest $request, string $id)
    {
        $model = HostGroup::forUser(Auth::user())->findOrFail($id);
        $model->fill($request->only([
            'name',
        ]));
        $model->save();
        return $this->responseIdMeta($request, $model->id, 200);
    }

    public function destroy(Request $request, string $id)
    {
        $model = HostGroup::forUser($request->user())->findOrFail($id);
        $model->delete();
        return response()->json([], 204);
    }
}
