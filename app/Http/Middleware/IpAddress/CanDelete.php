<?php
namespace App\Http\Middleware\IpAddress;

use App\Models\V2\IpAddress;
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
        $ipAddress = IpAddress::forUser($request->user())->findOrFail($request->route('ipAddressId'));
        if (!empty($ipAddress->nics()->count() > 0)) {
            return response()->json([
                'errors' => [
                    [
                        'title' => 'Forbidden',
                        'detail' => 'The IP address is in use',
                        'status' => 403,
                    ]
                ]
            ], 403);
        }

        return $next($request);
    }
}
