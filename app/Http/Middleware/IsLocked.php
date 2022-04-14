<?php
namespace App\Http\Middleware;

use Closure;

class IsLocked
{
    public function handle($request, Closure $next, $modelType, $idRouteParameter = 'id')
    {
        $model = $modelType::forUser($request->user())->findOrFail($request->route($idRouteParameter));
        if ($request->user()->isScoped() && $model->isSystem()) {
            return $this->returnError();
        }

        return $next($request);
    }

    public function returnError(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'errors' => [
                [
                    'title' => 'Forbidden',
                    'detail' => 'The specified resource is locked',
                    'status' => 403,
                ]
            ]
        ], 403);
    }
}
