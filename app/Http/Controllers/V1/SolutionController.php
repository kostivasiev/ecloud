<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;

use UKFast\Api\Resource\Traits\ResponseHelper;
use UKFast\Api\Resource\Traits\RequestHelper;
use UKFast\DB\Ditto\TransformsQueries;

use UKFast\Api\Exceptions\NotFoundException;
//use App\Exceptions\V1\SolutionNotFoundException;

use App\Models\V1\Solution;
use Illuminate\Http\Request;

class SolutionController extends Controller
{
    use ResponseHelper, RequestHelper, TransformsQueries;

    /**
     * List all solutions
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $collectionQuery = Solution::withReseller($request->user->resellerId)
            ->where('ucs_reseller_active', 'Yes');

        $this->transformQuery($collectionQuery, Solution::class);

        $solutions = $collectionQuery->paginate($request->input('per_page', env('PAGINATION_LIMIT')));

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
     * @throws NotFoundException
     */
    public function show(Request $request, $solutionId)
    {
        $this->validateSolutionId($request, $solutionId);

        $collectionQuery = Solution::withReseller($request->user->resellerId)
            ->where('ucs_reseller_active', 'Yes');

        $this->transformQuery($collectionQuery, Solution::class);

        $solution = $collectionQuery->find($solutionId);

        if (empty($solution)) {
//            throw new SolutionNotFoundException('Solution #' . $solutionId . ' not found', 'solution_id');
            throw new NotFoundException('Solution #' . $solutionId . ' not found', 'solution_id');
        }

        return $this->respondItem(
            $request,
            $solution
        );
    }

    /**
     * Validates the solution ID
     *
     * @param  Request $request
     * @param  int     $solutionId
     * @return void
     */
    protected function validateSolutionId(Request $request, $solutionId)
    {
        $request['solution_id'] = $solutionId;
        $this->validate($request, ['solution_id' => 'required|integer']);

        unset($request['solution_id']);
        $request['id'] = intval($solutionId);
    }
}
