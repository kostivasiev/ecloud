<?php

namespace App\Http\Middleware\Vpc;

use Closure;
use Illuminate\Support\Facades\Log;
use UKFast\Admin\Account\AdminClient;

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
        if ($request->has('support_enabled')) {
            if ($request->user()->isScoped()) {
                $accountAdminClient = app()->make(AdminClient::class);
                try {
                    $customer = $accountAdminClient->customers()->getById($request->user()->resellerId());
                } catch (\Exception $e) {
                    if ($e->getResponse()->getStatusCode() !== 404) {
                        Log::info($e);
                        return response()->json([
                            'errors' => [
                                [
                                    'title' => 'Error with Admin Client.',
                                    'detail' => 'The Admin Client is not available',
                                    'status' => 500,
                                ]
                            ]
                        ], 500);
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
        }

        return $next($request);
    }
}
