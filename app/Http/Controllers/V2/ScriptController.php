<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\Script\Create;
use App\Http\Requests\V2\Script\Update;
use App\Models\V2\Script;
use App\Resources\V2\ScriptResource;
use Illuminate\Http\Request;

class ScriptController extends BaseController
{
    public function index(Request $request)
    {
        $collection = Script::forUser($request->user());

        return ScriptResource::collection($collection->search()->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    public function show(Request $request, string $scriptId)
    {
        return new ScriptResource(
            Script::forUser($request->user())->findOrFail($scriptId)
        );
    }

    public function store(Create $request)
    {
        $resource = new Script($request->only([
            'name',
            'software_id',
            'sequence',
            'script',
        ]));
        $resource->save();
        return $this->responseIdMeta($request, $resource->id, 201);
    }

    public function update(Update $request, string $scriptId)
    {
        $vpnProfile = Script::forUser($request->user())->findOrFail($scriptId);
        $vpnProfile->fill($request->only([
            'name',
            'sequence',
            'script',
        ]));
        $vpnProfile->save();
        return $this->responseIdMeta($request, $vpnProfile->id, 200);
    }

    public function destroy(Request $request, string $scriptId)
    {
        Script::forUser($request->user())->findOrFail($scriptId)->delete();
        return response('', 204);
    }
}
