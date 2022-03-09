<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\HostSpec\Create;
use App\Http\Requests\V2\HostSpec\Update;
use App\Models\V2\HostSpec;
use App\Resources\V2\HostSpecResource;
use Illuminate\Http\Request;

class HostSpecController extends BaseController
{
    public function index(Request $request)
    {
        $collection = HostSpec::query();

        return HostSpecResource::collection(
            $collection->search()
                ->paginate(
                    $request->input('per_page', env('PAGINATION_LIMIT'))
                )
        );
    }

    public function show(Request $request, string $hostSpecId)
    {
        return new HostSpecResource(
            HostSpec::findOrFail($hostSpecId)
        );
    }

    public function store(Create $request)
    {
        $model = app()->make(HostSpec::class);
        $model->fill($request->only([
            'name',
            'ucs_specification_name',
            'cpu_sockets',
            'cpu_type',
            'cpu_cores',
            'cpu_clock_speed',
            'ram_capacity',
        ]));
        $model->save();

        if ($request->has('availability_zones')) {
            // Sync the pivot table
            $model->availabilityZones()->sync(collect($request->input('availability_zones'))->pluck('id')->toArray());
        }

        return $this->responseIdMeta($request, $model->id, 201);
    }

    public function update(Update $request, string $hostSpecId)
    {
        $model = HostSpec::findOrFail($hostSpecId);
        $model->fill($request->only([
            'name',
            'cpu_sockets',
            'cpu_type',
            'cpu_cores',
            'cpu_clock_speed',
            'ram_capacity',
        ]));
        $model->save();

        if ($request->has('availability_zones')) {
            // Sync the pivot table
            $model->availabilityZones()->sync(collect($request->input('availability_zones'))->pluck('id')->toArray());
        }

        return $this->responseIdMeta($request, $model->id, 200);
    }

    public function destroy(Request $request, string $hostSpecId)
    {
        $model = HostSpec::findOrFail($hostSpecId);
        // Delete from pivot table
        $model->availabilityZones()->sync([]);
        $model->delete();
        return response('', 204);
    }
}
