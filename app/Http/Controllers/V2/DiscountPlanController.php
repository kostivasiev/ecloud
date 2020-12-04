<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\DiscountPlan\Create;
use App\Http\Requests\V2\DiscountPlan\Update;
use App\Models\V2\DiscountPlan;
use App\Resources\V2\DiscountPlanResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use UKFast\DB\Ditto\QueryTransformer;

/**
 * Class DiscountPlanController
 * @package App\Http\Controllers\V2
 */
class DiscountPlanController extends BaseController
{
    /**
     * @param Request $request
     * @return Response
     */
    public function index(Request $request)
    {
        $collection = DiscountPlan::forUser($request->user);
        (new QueryTransformer($request))
            ->config(DiscountPlan::class)
            ->transform($collection);

        return DiscountPlanResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    /**
     * @param Request $request
     * @param string $discountPlanId
     * @return DiscountPlanResource
     */
    public function show(Request $request, string $discountPlanId)
    {
        return new DiscountPlanResource(
            DiscountPlan::forUser($request->user)->findOrFail($discountPlanId)
        );
    }

    /**
     * @param Create $request
     * @return JsonResponse
     */
    public function store(Create $request)
    {
        $discountPlan = new DiscountPlan($request->only([
            'contact_id',
            'employee_id',
            'name',
            'commitment_amount',
            'commitment_before_discount',
            'discount_rate',
            'term_length',
            'term_start_date',
            'term_end_date',
        ]));
        $discountPlan->reseller_id = $this->resellerId;
        $discountPlan->save();
        return $this->responseIdMeta($request, $discountPlan->getKey(), 201);
    }

    /**
     * @param Update $request
     * @param string $discountPlanId
     * @return JsonResponse
     */
    public function update(Update $request, string $discountPlanId)
    {
        $discountPlan = DiscountPlan::forUser(app('request')->user)->findOrFail($discountPlanId);
        $discountPlan->update($request->only([
            'contact_id',
            'employee_id',
            'name',
            'commitment_amount',
            'commitment_before_discount',
            'discount_rate',
            'term_length',
            'term_start_date',
            'term_end_date',
        ]));

        if ($this->isAdmin) {
            $discountPlan->reseller_id = $request->input('reseller_id', $discountPlan->reseller_id);
        }
        $discountPlan->save();
        return $this->responseIdMeta($request, $discountPlan->getKey(), 200);
    }

    /**
     * @param string $discountPlanId
     * @return JsonResponse
     * @throws \Exception
     */
    public function destroy(string $discountPlanId)
    {
        $discountPlan = DiscountPlan::forUser(app('request')->user)->findOrFail($discountPlanId);
        $discountPlan->delete();
        return response()->json([], 204);
    }
}
