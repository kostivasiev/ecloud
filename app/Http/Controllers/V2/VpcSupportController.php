<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\VpcSupport\CreateRequest;
use App\Http\Requests\V2\VpcSupport\UpdateRequest;
use App\Models\V2\VpcSupport;
use App\Resources\V2\VpcSupportResource;
use Illuminate\Http\Request;
use UKFast\DB\Ditto\QueryTransformer;

class VpcSupportController extends BaseController
{
    public function index(Request $request, QueryTransformer $queryTransformer)
    {
        $collection = VpcSupport::forUser($request->user);

        $queryTransformer->config(VpcSupport::class)
            ->transform($collection);

        return VpcSupportResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    public function show(Request $request, string $vpcSupportId)
    {
        return new VpcSupportResource(
            VpcSupport::forUser($request->user)->findOrFail($vpcSupportId)
        );
    }

    public function create(CreateRequest $request)
    {
        $vpcSupport = new VpcSupport($request->only([
            'vpc_id',
            'start_date',
            'end_date',
        ]));
        $vpcSupport->save();
        return $this->responseIdMeta($request, $vpcSupport->getKey(), 201);
    }

    public function update(UpdateRequest $request, string $vpcSupportId)
    {
        $vpcSupport = VpcSupport::forUser(app('request')->user)->findOrFail($vpcSupportId);

        $vpcSupport->fill($request->only([
            'vpc_id',
            'start_date',
            'end_date',
        ]))->save();

        return $this->responseIdMeta($request, $vpcSupport->getKey(), 200);
    }

    public function destroy(Request $request, string $vpcId)
    {
        VpcSupport::forUser($request->user)->findOrFail($vpcId)
            ->delete();
        return response(null, 204);
    }
}
