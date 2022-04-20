<?php
namespace App\Http\Middleware\FirewallPolicy;

use Closure;
use Illuminate\Http\Request;

class IsSystem
{
    public function handle(Request $request, Closure $next, $modelType, $idRouteParameter = 'id')
    {
        $modelInstanceId = ($request->method() == 'POST') ?
            $request->input($idRouteParameter):
            $request->route($idRouteParameter);
        $model = $modelType::forUser($request->user())->findOrFail($modelInstanceId);
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
                    'detail' => 'The System policy is not editable',
                    'status' => 403,
                ]
            ]
        ], 403);
    }
}
