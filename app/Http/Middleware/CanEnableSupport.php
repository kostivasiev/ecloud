<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

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
     * @throws \Exception
     */
    public function handle($request, Closure $next)
    {
        if ($request->user()->isScoped()) {
            $accountAdminClient = app()->make(\UKFast\Admin\Account\AdminClient::class);
            try {
                $customer = $accountAdminClient->customers()->getById($request->user()->resellerId());
            } catch (\Exception $e) {
                if ($e->getResponse()->getStatusCode() !== 404) {
                    Log::info($e);
                    throw($e);
                }
                return response()->json([
                    'errors' => [
                        [
                            'title' => 'Not Found',
                            'detail' => 'The customer account is not available',
                            'status' => 403,
                        ]
                    ]
                ], 403);
            }

            if ($customer->paymentMethod == 'Credit Card') {
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
