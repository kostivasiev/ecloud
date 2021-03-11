<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;

/**
 * Class CanEnableSupport
 * @package App\Http\Middleware
 *
 * Determine if the user can the user enable support on the VPC
 */
class CanEnableSupport
{
    /**
     * @param $request
     * @param Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if ($request->user()->isScoped()) {
            $accountAdminClient = app()->make(\UKFast\Admin\Account\AdminClient::class);
            $paymentMethod = $accountAdminClient->customers()->getById($request->user()->resellerId())->paymentMethod;

            if ($paymentMethod == 'Credit Card') {
                return response()->json([
                    'errors' => [
                        [
                            'title' => 'Payment Required',
                            'detail' => 'Payment is required before support can be enabled',
                            'status' => 402,
                        ]
                    ]
                ], 402);
            }
        }

        return $next($request);
    }
}
