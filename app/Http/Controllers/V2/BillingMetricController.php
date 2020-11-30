<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\BillingMetric\CreateRequest;
use App\Http\Requests\V2\BillingMetric\UpdateRequest;
use App\Models\V2\BillingMetric;
use App\Resources\V2\BillingMetricResource;
use Illuminate\Http\Request;
use UKFast\DB\Ditto\QueryTransformer;

class BillingMetricController extends BaseController
{
    public function index(Request $request, QueryTransformer $queryTransformer)
    {
        $collection = BillingMetric::forUser($request->user);
        $queryTransformer->config(BillingMetric::class)
            ->transform($collection);
        return BillingMetricResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    public function show(Request $request, string $modelId)
    {
        return new BillingMetricResource(BillingMetric::forUser($request->user)->findOrFail($modelId));
    }

    public function create(CreateRequest $request)
    {
        $model = new BillingMetric($request->only([
            'resource_id',
            'key',
            'value',
            'cost',
            'start',
            'end',
        ]));
        $model->save();
        return $this->responseIdMeta($request, $model->getKey(), 201);
    }

    public function update(UpdateRequest $request, string $modelId)
    {
        $model = BillingMetric::forUser(app('request')->user)->findOrFail($modelId);
        $model->fill($request->only([
            'resource_id',
            'key',
            'value',
            'cost',
            'start',
            'end',
        ]));
        $model->save();
        return $this->responseIdMeta($request, $model->getKey(), 200);
    }

    public function destroy(Request $request, string $modelId)
    {
        $model = BillingMetric::forUser($request->user)->findOrFail($modelId);
        $model->delete();
        return response()->json([], 204);
    }
}
