<?php

namespace App\Http\Middleware\Instance;

use App\Models\V2\Instance;
use Closure;

class IsLocked
{
    public function handle($request, Closure $next)
    {
        $model = Instance::forUser($request->user())
            ->findOrFail($request->route('instanceId'));
        if ($request->user()->isScoped() && $model->locked === true) {
            return response()->json([
                'errors' => [
                    [
                        'title' => 'Forbidden',
                        'detail' => 'The specified Instance is locked',
                        'status' => 403,
                    ]
                ]
            ], 403);
        }

        return $next($request);
    }
}
