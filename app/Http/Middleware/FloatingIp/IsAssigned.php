<?php
namespace App\Http\Middleware\FloatingIp;

use App\Models\V2\FloatingIp;
use Closure;
use Symfony\Component\HttpFoundation\Response;

class IsAssigned
{
    /**
     * @param $request
     * @param Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $floatingIp = FloatingIp::forUser($request->user())->findOrFail($request->route('fipId'));

        if (!empty($floatingIp->resource_id)) {
            return response()->json([
                'errors' => [
                    [
                        'title' => 'Conflict',
                        'detail' => "The Floating IP is already assigned to a resource.",
                        'status' => Response::HTTP_CONFLICT,
                    ]
                ]
            ], Response::HTTP_CONFLICT);
        }

        return $next($request);
    }
}
