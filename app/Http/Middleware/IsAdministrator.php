<?php

namespace App\Http\Middleware;

use Closure;
use UKFast\Api\Exceptions\UnauthorisedException;

/**
 * Class IsAdministrator
 * @package App\Http\Middleware
 *
 * Ensure a route is only accessed by an Administrator
 */
class IsAdministrator
{
    /**
     * @param $request
     * @param Closure $next
     * @return mixed
     * @throws UnauthorisedException
     */
    public function handle($request, Closure $next)
    {
        if (!$request->user()->isAdmin()) {
            throw new UnauthorisedException();
        }

        return $next($request);
    }
}
