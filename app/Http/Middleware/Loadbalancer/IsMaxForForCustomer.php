<?php

namespace App\Http\Middleware\Loadbalancer;

use App\Exceptions\V2\MaxVpcException;
use App\Http\Middleware\ResellerBypass;
use App\Models\V2\Instance;
use Closure;
use Illuminate\Support\Facades\Auth;

class IsMaxForForCustomer
{
    use ResellerBypass;

    public function handle($request, Closure $next)
    {
        if ($this->resellerBypass()) {
            return $next($request);
        }


        return Instance::forUser(Auth::user())->count() < config('load-balancer.customer_max_per_az');


        $ipAddress = IpAddress::forUser($request->user())->findOrFail($request->route('ipAddressId'));
        if (!empty($ipAddress->nics()->count() > 0)) {
            return response()->json([
                'errors' => [
                    [
                        'title' => 'Forbidden',
                        'detail' => 'The IP address is in use',
                        'status' => 403,
                    ]
                ]
            ], 403);
        }

        return $next($request);
    }
}
