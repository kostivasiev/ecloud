<?php

namespace App\Http\Middleware\OrchestratorConfig;

use App\Models\V2\OrchestratorConfig;
use Closure;

class HasResellerId
{
    /**
     * @param $request
     * @param Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $orchestratorConfig = OrchestratorConfig::forUser($request->user())
            ->findOrFail($request->route('orchestratorConfigId'));

        if (!empty($orchestratorConfig) && empty($orchestratorConfig->reseller_id)) {
            return response()->json([
                'title' => 'Validation Error',
                'detail' => 'Orchestrator Config has no reseller_id',
                'status' => 422,
            ], 422);
        }

        return $next($request);
    }
}
