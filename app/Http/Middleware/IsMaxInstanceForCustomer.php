<?php

namespace App\Http\Middleware;

use App\Exceptions\V2\MaxInstanceException;
use App\Exceptions\V2\MaxVpcException;
use App\Models\V2\Instance;
use Closure;
use Illuminate\Support\Facades\Auth;

class IsMaxInstanceForCustomer
{
    use ResellerBypass;

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
        if ($this->resellerBypass()) {
            return true;
        }
        return Instance::forUser(Auth::user())->count() < config('instance.max_limit.total');
    }
}
