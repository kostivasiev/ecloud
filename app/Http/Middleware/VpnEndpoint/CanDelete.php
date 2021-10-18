<?php
namespace App\Http\Middleware\VpnEndpoint;

use App\Models\V2\VpnEndpoint;
use Closure;

class CanDelete
{
    /**
     * @param $request
     * @param Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $vpnEndpoint = VpnEndpoint::forUser($request->user())->findOrFail($request->route('vpnEndpointId'));
        if ($vpnEndpoint->vpnService->vpnSessions->count() > 0) {
            return response()->json([
                'errors' => [
                    [
                        'title' => 'Forbidden',
                        'detail' => 'Vpn Endpoints that have associated Vpn Sessions cannot be deleted',
                        'status' => 403,
                    ]
                ]
            ], 403);
        }

        return $next($request);
    }
}
