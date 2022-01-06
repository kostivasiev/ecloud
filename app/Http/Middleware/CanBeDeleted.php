<?php
namespace App\Http\Middleware;

use Closure;
use Symfony\Component\HttpFoundation\Response;

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
            return response()->json(
                [
                    'errors' => [
                        [
                            'title' => 'Precondition Failed',
                            'detail' => 'The specified resource has dependant relationships and cannot be deleted',
                            'status' => Response::HTTP_PRECONDITION_FAILED,
                        ],
                    ],
                ],
                Response::HTTP_PRECONDITION_FAILED
            );
        }

        return $next($request);
    }
}
