<?php

namespace App\Http\Middleware\OrchestratorConfig;

use App\Models\V2\OrchestratorConfig;
use Closure;

class IsLocked
{
    public function handle($request, Closure $next)
    {
        $model = OrchestratorConfig::forUser($request->user())
            ->findOrFail($request->route('orchestratorConfigId'));
        if ($model->locked === true) {
            return response()->json([
                'errors' => [
                    [
                        'title' => 'Forbidden',
                        'detail' => 'The specified Orchestrator Config is locked',
                        'status' => 403,
                    ]
                ]
            ], 403);
        }

        return $next($request);
    }
}
