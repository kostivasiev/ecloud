<?php
namespace App\Http\Middleware\FloatingIp;

use App\Models\V2\FloatingIp;
use App\Models\V2\VpnEndpoint;
use Closure;

class CanBeUnassigned
{
    /**
     * @param $request
     * @param Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $floatingIp = FloatingIp::forUser($request->user())->findOrFail($request->route('fipId'));

        if (!empty($floatingIp->resource_id) && $floatingIp->resource instanceof VpnEndpoint) {
            return response()->json([
                'errors' => [
                    [
                        'title' => 'Forbidden',
                        'detail' => 'Floating IP\'s can not be unassigned from a VPN endpoint',
                        'status' => 403,
                    ]
                ]
            ], 403);
        }

        return $next($request);
    }
}
