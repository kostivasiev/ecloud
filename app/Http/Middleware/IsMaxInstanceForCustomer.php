<?php

namespace App\Http\Middleware;

use App\Exceptions\V2\MaxInstanceException;
use App\Exceptions\V2\MaxVpcException;
use App\Models\V2\Instance;
use Closure;
use Illuminate\Support\Facades\Auth;

class IsMaxInstanceForCustomer
{
    /**
     * @param $request
     * @param Closure $next
     * @return mixed
     * @throws MaxVpcException
     */
    public function handle($request, Closure $next)
    {
        if (!$this->isWithinLimit()) {
            throw new MaxInstanceException();
        }

        return $next($request);
    }

    public function isWithinLimit(): bool
    {
        $reseller_bypass = [
            7052, // UKFast - eCloud Testing
            22114, // UKFast - eCloud Automated Testing
        ];

        if (in_array(Auth::user()->resellerId(), $reseller_bypass)) {
            return true;
        }
        return Instance::forUser(Auth::user())->count() < config('instance.max_limit.total');
    }
}
