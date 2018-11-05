<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;

use UKFast\DB\Ditto\QueryTransformer;

use UKFast\Api\Resource\Traits\ResponseHelper;
use UKFast\Api\Resource\Traits\RequestHelper;

use Illuminate\Http\Request;

use App\Models\V1\Solution;
use App\Exceptions\V1\SolutionNotFoundException;

class SolutionController extends Controller
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

    /**
     * List all solutions
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $collectionQuery = Solution::withReseller($request->user->resellerId);
        if (!$request->user->isAdmin) {
            $collectionQuery->where('ucs_reseller_active', 'Yes');
        }

        (new QueryTransformer($request))
            ->config(Solution::class)
            ->transform($collectionQuery);

        $solutions = $collectionQuery->paginate($this->per_page);

        return $this->respondCollection(
            $request,
            $solutions
        );
    }

    /**
     * Show specific solution
     *
     * @param Request $request
     * @param $solutionId
     * @return \Illuminate\http\Response
     * @throws SolutionNotFoundException
     */
    public function show(Request $request, $solutionId)
    {
        $solutionQuery = Solution::withReseller($request->user->resellerId);
        if (!$request->user->isAdmin) {
            $solutionQuery->where('ucs_reseller_active', 'Yes');
        }

        $solution = $solutionQuery->find($solutionId);
        if (is_null($solution)) {
            throw new SolutionNotFoundException('Solution ID #' . $solutionId . ' not found', 'solution_id');
        }

        return $this->respondItem(
            $request,
            $solution
        );
    }
}
