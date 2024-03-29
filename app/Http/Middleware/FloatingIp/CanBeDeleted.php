<?php
namespace App\Http\Middleware\FloatingIp;

use App\Models\V2\FloatingIp;
use Closure;

class CanBeDeleted
{
    /**
     * @param $request
     * @param Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $floatingIp = FloatingIp::forUser($request->user())->findOrFail($request->route('fipId'));

        if ($floatingIp->floatingIpResource()->exists()) {
            return response()->json([
                'errors' => [
                    [
                        'title' => 'Forbidden',
                        'detail' => 'Floating IP\'s assigned to a resource can not be deleted',
                        'status' => 403,
                    ]
                ]
            ], 403);
        }

        return $next($request);
    }
}
