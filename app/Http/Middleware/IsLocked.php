<?php

namespace App\Http\Middleware;

use App\Models\V2\Instance;
use Closure;
use Illuminate\Http\JsonResponse;

/**
 * Class IsLocked
 * @package App\Http\Middleware
 *
 * Is an instance locked from updating
 */
class IsLocked
{
    /**
     * @param $request
     * @param Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $instance = Instance::forUser($request->user())->findOrFail($request->route('instanceId'));

        if ($request->user()->isScoped() && $instance->locked) {
            return JsonResponse::create([
                'errors' => [
                    [
                        'title' => 'Forbidden',
                        'detail' => 'The specified instance is locked',
                        'status' => 403,
                    ]
                ]
            ], 403);
        }

        return $next($request);
    }
}
