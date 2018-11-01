<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;

use UKFast\Api\Resource\Traits\ResponseHelper;
use UKFast\Api\Resource\Traits\RequestHelper;
use UKFast\DB\Ditto\TransformsQueries;

use App\Models\V1\Solution;
use Illuminate\Http\Request;

class SolutionController extends Controller
{
    use ResponseHelper, RequestHelper, TransformsQueries;

    /**
     * List all solutions
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $collection = Solution::withReseller($request->user->resellerId);
        $this->transformQuery($collection, Solution::class);

        $solutions = $collection->paginate($request->input('per_page', env('PAGINATION_LIMIT')));
        return $this->respondCollection($request, $solutions);
    }
}
