<?php
namespace App\Http\Middleware;

use Closure;

class CanBeDeleted
{
    /**
     * @param $request
     * @param Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next, $modelType, $idRouteParameter = 'id')
    {
        $model = $modelType::forUser($request->user())->findOrFail($request->route($idRouteParameter));
        if (!$model->canDelete()) {
            return $model->getDeletionError();
        }

        return $next($request);
    }
}
