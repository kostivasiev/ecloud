<?php

namespace App\Http\Middleware;

use App\Exceptions\V2\MaxVpcException;
use App\Models\V2\Vpc;
use Closure;
use Illuminate\Support\Facades\Auth;

class IsMaxVpcForCustomer
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
            throw new MaxVpcException();
        }

        return $next($request);
    }

    public function isWithinLimit(): bool
    {
        if ($this->resellerBypass()) {
            return true;
        }
        return (Vpc::forUser(Auth::user())->get()->count() < config('defaults.vpc.max_count'));
    }
}
