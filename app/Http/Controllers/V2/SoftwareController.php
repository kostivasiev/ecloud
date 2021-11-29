<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\Software\Create;
use App\Http\Requests\V2\Software\Update;
use App\Models\V2\Software;
use App\Resources\V2\SoftwareResource;
use Illuminate\Http\Request;
use UKFast\DB\Ditto\QueryTransformer;

class SoftwareController extends BaseController
{
    public function index(Request $request, QueryTransformer $queryTransformer)
    {
        $collection = Software::query();

        $queryTransformer->config(Software::class)
            ->transform($collection);

        return SoftwareResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    public function show(Request $request, string $softwareId)
    {
        return new SoftwareResource(
            Software::findOrFail($softwareId)
        );
    }

    public function store(Create $request)
    {
        $resource = new Software($request->only([
            'name',
            'platform',
            'visibility',
        ]));
        $resource->save();

        return $this->responseIdMeta($request, $resource->id, 201);
    }

    public function update(Update $request, string $softwareId)
    {
        $resource = Software::findOrFail($softwareId);
        $resource->fill($request->only([
            'name',
            'platform',
            'visibility',
        ]));
        $resource->save();
        return $this->responseIdMeta($request, $resource->id, 200);
    }

    public function destroy(Request $request, string $softwareId)
    {
        Software::findOrFail($softwareId)->delete();
        return response('', 204);
    }
}
