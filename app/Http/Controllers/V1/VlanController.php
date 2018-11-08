<?php

namespace App\Http\Controllers\V1;

use UKFast\DB\Ditto\QueryTransformer;

use UKFast\Api\Resource\Traits\ResponseHelper;
use UKFast\Api\Resource\Traits\RequestHelper;

use Illuminate\Http\Request;

use App\Models\V1\Vlan;

class VlanController extends BaseController
{
    use ResponseHelper, RequestHelper;

    public function getSolutionVlans(Request $request, $solutionId)
    {
        $solution = SolutionController::getSolutionById($request, $solutionId);

        $collectionQuery = Vlan::withReseller($request->user->resellerId)
            ->withSolution($solution->ucs_reseller_id);

        if (!$request->user->isAdmin) {
            $collectionQuery->where('ucs_reseller_active', 'Yes');
        }

        (new QueryTransformer($request))
            ->config(Vlan::class)
            ->transform($collectionQuery);

        $vlans = $collectionQuery->paginate($this->perPage);

        return $this->respondCollection(
            $request,
            $vlans
        );
    }
}
