<?php

namespace App\Http\Middleware\Instance;

use App\Models\V2\Instance;
use Closure;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CanMigrate
{
    public function handle($request, Closure $next)
    {
        $instance = Instance::forUser(Auth::user())->findOrFail($request->route('instanceId'));

        if ($instance->affinityRuleMember !== null) {
            return response()->json([
                'errors' => [
                    'title' => 'Forbidden',
                    'details' => 'This resource is assigned to an Affinity Rule and cannot be moved.',
                    'status' => Response::HTTP_FORBIDDEN,
                ]
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
