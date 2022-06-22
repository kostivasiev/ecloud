<?php
namespace App\Http\Middleware\HostGroup;

use App\Models\V2\FloatingIp;
use App\Models\V2\HostGroup;
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
        $hostGroup = HostGroup::forUser($request->user())->findOrFail($request->route('id'));

        if ($hostGroup->hostSpec->is_hidden) {
            return response()->json([
                'errors' => [
                    [
                        'title' => 'Forbidden',
                        'detail' => 'This HostGroup cannot be deleted',
                        'status' => 403,
                    ]
                ]
            ], 403);
        }

        return $next($request);
    }
}
