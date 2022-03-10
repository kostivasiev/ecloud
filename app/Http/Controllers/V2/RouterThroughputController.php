<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\RouterThroughput\CreateRequest;
use App\Http\Requests\V2\RouterThroughput\UpdateRequest;
use App\Models\V2\RouterThroughput;
use App\Resources\V2\RouterThroughputResource;
use Illuminate\Http\Request;

class RouterThroughputController extends BaseController
{
    public function index(Request $request)
    {
        $collection = RouterThroughput::forUser($request->user());

        return RouterThroughputResource::collection($collection->search()->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    public function show(Request $request, string $routerThroughputId)
    {
        return new RouterThroughputResource(
            RouterThroughput::forUser($request->user())->findOrFail($routerThroughputId)
        );
    }

    public function store(CreateRequest $request)
    {
        $routerThroughput = new RouterThroughput($request->only([
            'name',
            'availability_zone_id',
            'committed_bandwidth',
        ]));
        $routerThroughput->save();
        return $this->responseIdMeta($request, $routerThroughput->id, 201);
    }

    public function update(UpdateRequest $request, string $routerThroughputId)
    {
        $routerThroughput = RouterThroughput::findOrFail($routerThroughputId);
        $routerThroughput->fill($request->only([
            'name',
            'availability_zone_id',
            'committed_bandwidth',
        ]));
        $routerThroughput->save();
        return $this->responseIdMeta($request, $routerThroughput->id, 200);
    }

    public function destroy(Request $request, string $routerThroughputId)
    {
        RouterThroughput::findOrFail($routerThroughputId)->delete();
        return response('', 204);
    }
}
