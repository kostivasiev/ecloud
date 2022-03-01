<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\Software\Create;
use App\Http\Requests\V2\Software\Update;
use App\Models\V2\Software;
use App\Resources\V2\ImageResource;
use App\Resources\V2\ScriptResource;
use App\Resources\V2\SoftwareResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use UKFast\DB\Ditto\QueryTransformer;

class SoftwareController extends BaseController
{
    public function index(Request $request, QueryTransformer $queryTransformer)
    {
        $collection = Software::forUser($request->user());

        $queryTransformer->config(Software::class)
            ->transform($collection);

        return SoftwareResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    public function show(Request $request, string $softwareId)
    {
        return new SoftwareResource(
            Software::forUser($request->user())->findOrFail($softwareId)
        );
    }

    public function store(Create $request)
    {
        $resource = new Software($request->only([
            'name',
            'platform',
            'visibility',
            'license',
        ]));
        $resource->save();

        return $this->responseIdMeta($request, $resource->id, 201);
    }

    public function update(Update $request, string $softwareId)
    {
        $resource = Software::forUser(Auth::user())->findOrFail($softwareId);
        $resource->fill($request->only([
            'name',
            'platform',
            'visibility',
            'license',
        ]));
        $resource->save();
        return $this->responseIdMeta($request, $resource->id, 200);
    }

    public function destroy(Request $request, string $softwareId)
    {
        Software::forUser($request->user())->findOrFail($softwareId)->delete();
        return response('', 204);
    }

    public function scripts(Request $request, string $softwareId)
    {
        $collection = Software::forUser(Auth::user())->findOrFail($softwareId)->scripts();

        return ScriptResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    public function images(Request $request, string $softwareId)
    {
        $collection = Software::forUser(Auth::user())
            ->findOrFail($softwareId)
            ->images()
            ->forUser(Auth::user());

        return ImageResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }
}
