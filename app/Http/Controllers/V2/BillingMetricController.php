<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\BillingMetric\CreateRequest;
use App\Http\Requests\V2\BillingMetric\UpdateRequest;
use App\Models\V2\BillingMetric;
use App\Resources\V2\BillingMetricResource;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use UKFast\DB\Ditto\QueryTransformer;

class BillingMetricController extends BaseController
{
    public function index(Request $request, QueryTransformer $queryTransformer)
    {
        $collection = BillingMetric::forUser($request->user());
        $queryTransformer->config(BillingMetric::class)
            ->transform($collection);
        return BillingMetricResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    public function show(Request $request, string $billingMetricId)
    {
        return new BillingMetricResource(BillingMetric::forUser($request->user())->findOrFail($billingMetricId));
    }

    public function create(CreateRequest $request)
    {
        if ($request->input('end') == null && !$this->isAdmin) {
            return response()->json([
                'errors' => [
                    'title' => 'Forbidden',
                    'details' => 'End date can only be set to null by admins.',
                    'status' => Response::HTTP_FORBIDDEN,
                ]
            ], Response::HTTP_FORBIDDEN);
        }

        $model = new BillingMetric($request->only([
            'resource_id',
            'vpc_id',
            'reseller_id',
            'name',
            'key',
            'value',
            'start',
            'end',
            'category',
            'price',
        ]));
        $model->save();
        return $this->responseIdMeta($request, $model->id, 201);
    }

    public function update(UpdateRequest $request, string $billingMetricId)
    {
        if ($request->input('end') == null && !$this->isAdmin) {
            return response()->json([
                'errors' => [
                    'title' => 'Forbidden',
                    'details' => 'End date can only be set to null by admins.',
                    'status' => Response::HTTP_FORBIDDEN,
                ]
            ], Response::HTTP_FORBIDDEN);
        }

        $model = BillingMetric::forUser(Auth::user())->findOrFail($billingMetricId);
        $model->fill($request->only([
            'resource_id',
            'vpc_id',
            'reseller_id',
            'name',
            'key',
            'value',
            'start',
            'end',
            'category',
            'price',
        ]));
        $model->save();
        return $this->responseIdMeta($request, $model->id, 200);
    }

    public function destroy(Request $request, string $billingMetricId)
    {
        $model = BillingMetric::forUser($request->user())->findOrFail($billingMetricId);
        $model->delete();
        return response('', 204);
    }
}
