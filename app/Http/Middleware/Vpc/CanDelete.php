<?php
namespace App\Http\Middleware\Vpc;

use App\Models\V2\Vpc;
use Closure;
use Symfony\Component\HttpFoundation\Response;

class CanDelete
{
    /**
     * @param $request
     * @param Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $vpc = Vpc::findOrFail($request->route('vpcId'));

        $relationships = collect(
            $vpc->with($vpc->children)
                ->findOrFail($vpc->id)
                ->getRelations()
        )->sum(function ($relation) {
            return $relation->count();
        });
        $managementRouterCount = $vpc->whereHas('routers', function ($query) {
            $query->where('is_hidden', '=', 1);
        })->count();
        $relationships = $relationships - $managementRouterCount;

        if ($relationships > 0) {
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
