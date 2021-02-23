<?php

namespace App\Http\Middleware;

use Closure;
use UKFast\Api\Exceptions\BadRequestException;

/**
 * Class HasResellerId
 * @package App\Http\Middleware
 *
 * Ensure a route is scoped to a reseller id
 */
class HasResellerId
{
    /**
     * @param \Illuminate\Http\Request $request
     * @param Closure $next
     * @return mixed
     * @throws BadRequestException
     */
    public function handle($request, Closure $next)
    {
        if (empty($request->user()->resellerId())) {
            throw new BadRequestException('Missing Reseller scope');
        }

        return $next($request);
    }
}
