<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\HostSpec\Create;
use App\Http\Requests\V2\HostSpec\Update;
use App\Models\V2\HostSpec;
use App\Resources\V2\HostSpecResource;
use Illuminate\Http\Request;
use UKFast\DB\Ditto\QueryTransformer;

class HostSpecController extends BaseController
{
    public function index(Request $request, QueryTransformer $queryTransformer)
    {
        $collection = HostSpec::query();
        $queryTransformer->config(HostSpec::class)
            ->transform($collection);

        return HostSpecResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    public function show(Request $request, string $hostSpecId)
    {
        return new HostSpecResource(
            HostSpec::findOrFail($hostSpecId)
        );
    }

    public function store(Create $request)
    {
        $resource = new HostSpec($request->only([
            'name',
            'cpu_sockets',
            'cpu_type',
            'cpu_cores',
            'cpu_clock_speed',
            'ram_capacity',
        ]));
        $resource->save();

        if ($request->has('availability_zones')) {
            // Sync the pivot table
            $resource->availabilityZones()->sync(collect($request->input('availability_zones'))->pluck('id')->toArray());
        }

        return $this->responseIdMeta($request, $resource->id, 201);
    }

    public function update(Update $request, string $hostSpecId)
    {
        $resource = HostSpec::findOrFail($hostSpecId);
        $resource->fill($request->only([
            'name',
            'cpu_sockets',
            'cpu_type',
            'cpu_cores',
            'cpu_clock_speed',
            'ram_capacity',
        ]));
        $resource->save();

        if ($request->has('availability_zones')) {
            // Sync the pivot table
            $resource->availabilityZones()->sync(collect($request->input('availability_zones'))->pluck('id')->toArray());
        }

        return $this->responseIdMeta($request, $resource->id, 200);
    }

    public function destroy(Request $request, string $hostSpecId)
    {
        $resource = HostSpec::findOrFail($hostSpecId);
        // Delete from pivot table
        $resource->availabilityZones()->sync([]);
        $resource->delete();
        return response(null, 204);
    }
}
