<?php

namespace App\Http\Middleware\OrchestratorConfig;

use Closure;

class IsValid
{
    /**
     * @param $request
     * @param Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (!$request->isJson() || empty(json_decode($request->getContent()))) {
            return response()->json([
                'title' => 'Validation Error',
                'detail' => 'The request does not contain a valid Orchestrator config',
                'status' => 422,
            ], 422);
        }

        return $next($request);
    }
}
