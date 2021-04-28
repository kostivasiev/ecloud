<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\Host\StoreRequest;
use App\Http\Requests\V2\Host\UpdateRequest;
use App\Models\V2\Host;
use App\Resources\V2\HostResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use UKFast\DB\Ditto\QueryTransformer;

class HostController extends BaseController
{
    public function index(Request $request, QueryTransformer $queryTransformer)
    {
        $collection = Host::forUser($request->user());

        $queryTransformer->config(Host::class)
            ->transform($collection);

        return HostResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    public function show(Request $request, string $id)
    {
        return new HostResource(
            Host::forUser($request->user())->findOrFail($id)
        );
    }

    public function store(StoreRequest $request)
    {
        $model = app()->make(Host::class);
        $model->fill($request->only([
            'name',
            'host_group_id',
        ]));
        $model->save();
        return $this->responseIdMeta($request, $model->id, 202);
    }

    public function update(UpdateRequest $request, string $id)
    {
        $model = Host::forUser(Auth::user())->findOrFail($id);
        $model->fill($request->only([
            'name',
        ]));

        $model->withSyncLock(function ($model) {
            $model->delete();
        });
        return $this->responseIdMeta($request, $model->id, 202);
    }

    public function destroy(Request $request, string $id)
    {
        $model = Host::forUser($request->user())->findOrFail($id);

        // This will be needed in a future issue, no need to delete it
//        if ($model->instances()->count()) {
//            return response()->json([
//                'title' => 'Validation Error',
//                'detail' => 'Can not delete Host with active instances',
//                'status' => 422,
//            ], 422);
//        }

        $model->withSyncLock(function ($model) {
            $model->delete();
        });
        return response('', 204);
    }
}
