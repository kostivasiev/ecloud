<?php

namespace App\Http\Controllers\V1;

use UKFast\DB\Ditto\QueryTransformer;

use UKFast\Api\Resource\Traits\ResponseHelper;
use UKFast\Api\Resource\Traits\RequestHelper;

use Illuminate\Http\Request;

use App\Models\V1\SolutionSite;

class SolutionSiteController extends BaseController
{
    use ResponseHelper, RequestHelper;

    public function getSolutionSites(Request $request, $solutionId)
    {
        $solution = SolutionController::getSolutionById($request, $solutionId);

        $collectionQuery = SolutionSite::withSolution($solution->id);

        (new QueryTransformer($request))
            ->config(SolutionSite::class)
            ->transform($collectionQuery);

        return $this->respondCollection(
            $request,
            $collectionQuery->paginate($this->perPage)
        );
    }
}
