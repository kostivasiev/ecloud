<?php
namespace App\Http\Middleware\Vpc;

use App\Models\V2\Vpc;
use App\Models\V2\VpnEndpoint;
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
        $vpcRelations = $vpc->with($vpc->children)
            ->whereHas('routers', function ($query) {
                $query->where('is_hidden', '=', false);
            })->find($vpc->id);

        if ($vpcRelations) {
            $relationships = collect($vpcRelations->getRelations())->sum(function ($relation) {
                return $relation->count();
            });
            if ($relationships !== 0) {
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
        }

        return $next($request);
    }
}
