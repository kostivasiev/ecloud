<?php

namespace App\Http\Middleware\DiscountPlan;

use App\Models\V2\DiscountPlan;
use Closure;
use Illuminate\Http\JsonResponse;

/**
 * Class IsPending
 * @package App\Http\Middleware
 */
class IsPending
{
    /**
     * @param $request
     * @param Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $discountPlan = DiscountPlan::forUser($request->user())->findOrFail($request->route('discountPlanId'));

        if ($discountPlan->status != 'pending') {
            return response()->json([
                'errors' => [
                    [
                        'title' => 'Forbidden',
                        'detail' => 'The discount plan has already been actioned',
                        'status' => 403,
                    ]
                ]
            ], 403);
        }

        return $next($request);
    }
}
