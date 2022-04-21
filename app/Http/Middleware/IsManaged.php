<?php
namespace App\Http\Middleware;

use App\Models\V2\Manageable;
use Closure;
use Illuminate\Http\Request;

class IsManaged
{
    public function handle(Request $request, Closure $next, $modelType, $idRouteParameter = 'id')
    {
        $idParameter = ($request->method() == 'POST') ?
            $request->$idRouteParameter:
            $request->route($idRouteParameter);
        $resource = $modelType::forUser($request->user())->findOrFail($idParameter);
        if ($request->user()->isScoped() && $resource instanceof Manageable && $resource->isManaged()) {
            return response()->json([
                'errors' => [
                    [
                        'title' => 'Forbidden',
                        'detail' => 'The specified resource is not editable',
                        'status' => 403,
                    ]
                ]
            ], 403);
        }

        return $next($request);
    }
}
