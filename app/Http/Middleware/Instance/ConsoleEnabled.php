<?php

namespace App\Http\Middleware\Instance;

use App\Models\V2\Instance;
use Closure;
use Illuminate\Http\Response;

class ConsoleEnabled
{
    public function handle($request, Closure $next)
    {
        $instance = Instance::forUser($request->user())->findOrFail($request->route('instanceId'));

        if (!$instance->vpc->console_enabled && !$request->user()->isAdmin()) {
            return response()->json([
                'errors' => [
                    'title' => 'Forbidden',
                    'details' => 'Console access has been disabled for this resource',
                    'status' => Response::HTTP_FORBIDDEN,
                ]
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
