<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;

use UKFast\DB\Ditto\QueryTransformer;

use UKFast\Api\Resource\Traits\ResponseHelper;
use UKFast\Api\Resource\Traits\RequestHelper;

use Illuminate\Http\Request;

use App\Models\V1\Vlan;
use App\Exceptions\V1\VlanNotFoundException;

class VlanController extends Controller
{
    use ResponseHelper, RequestHelper;

    private $per_page;

    /**
     * Controller constructor.
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        // Pagination limit. Try to set from Request, or default to .env PAGINATION_LIMIT
        $this->per_page = $request->input('per_page', env('PAGINATION_LIMIT'));

        $request->user->isAdmin = ($request->user->resellerId === 0);
    }

    public function getSolutionVlans(Request $request, $solutionId)
    {
        $collectionQuery = Vlan::withReseller($request->user->resellerId)
            ->withSolution($solutionId);

        if (!$request->user->isAdmin) {
            $collectionQuery->where('ucs_reseller_active', 'Yes');
        }

        (new QueryTransformer($request))
            ->config(Vlan::class)
            ->transform($collectionQuery);

        $vlans = $collectionQuery->paginate($this->per_page);

        return $this->respondCollection(
            $request,
            $vlans
        );
    }
}
