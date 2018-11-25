<?php

namespace App\Http\Controllers\V1;

use UKFast\DB\Ditto\QueryTransformer;

use UKFast\Api\Resource\Traits\ResponseHelper;
use UKFast\Api\Resource\Traits\RequestHelper;

use Illuminate\Http\Request;

use App\Models\V1\SolutionVlan;

class SolutionVlanController extends BaseController
{
    use ResponseHelper, RequestHelper;

    public function getSolutionVlans(Request $request, $solutionId)
    {
        SolutionController::getSolutionById($request, $solutionId);

        $collectionQuery = SolutionVlan::withReseller($request->user->resellerId)
            ->withSolution($solutionId);

        if (!$request->user->isAdmin) {
            $collectionQuery->where('ucs_reseller_active', 'Yes');
        }

        (new QueryTransformer($request))
            ->config(SolutionVlan::class)
            ->transform($collectionQuery);

        return $this->respondCollection(
            $request,
            $collectionQuery->paginate($this->perPage)
        );
    }
}
