<?php

namespace App\Http\Controllers\V1;

use UKFast\DB\Ditto\QueryTransformer;

use UKFast\Api\Resource\Traits\ResponseHelper;
use UKFast\Api\Resource\Traits\RequestHelper;

use Illuminate\Http\Request;

use App\Models\V1\Solution;
use App\Exceptions\V1\SolutionNotFoundException;

class SolutionController extends BaseController
{
    use ResponseHelper, RequestHelper;

    /**
     * List all solutions
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $collectionQuery = static::getSolutionQuery($request);

        (new QueryTransformer($request))
            ->config(Solution::class)
            ->transform($collectionQuery);

        $solutions = $collectionQuery->paginate($this->perPage);

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
        return $this->respondItem(
            $request,
            static::getSolutionById($request, $solutionId)
        );
    }

    /**
     * Update Solution
     *
     * @param Request $request
     * @param $solutionId
     * @return \Illuminate\Http\Response
     * @throws SolutionNotFoundException
     * @throws \UKFast\Api\Resource\Exceptions\InvalidResourceException
     * @throws \UKFast\Api\Resource\Exceptions\InvalidResponseException
     * @throws \UKFast\Api\Resource\Exceptions\InvalidRouteException
     */
    public function update(Request $request, $solutionId)
    {
        $solution = static::getSolutionQuery($request)->find($solutionId);
        if (is_null($solution)) {
            throw new SolutionNotFoundException('Solution ID #' . $solutionId . ' not found');
        }

        // replace request data with json payload
        $request->request->replace($request->json()->all());

        $this->validate($request, [
            'name' => 'regex:/'.Solution::NAME_FORMAT_REGEX.'/',
        ]);

        $solution->ucs_reseller_solution_name = $request->input('name');
        if (!$solution->save()) {
            //
        }

        return $this->respondSave($request, $solution);
    }

    /**
     * get solution by ID
     * @param Request $request
     * @param $solutionId
     * @return mixed
     * @throws SolutionNotFoundException
     */
    public static function getSolutionById(Request $request, $solutionId)
    {
        $solution = static::getSolutionQuery($request)->find($solutionId);
        if (is_null($solution)) {
            throw new SolutionNotFoundException('Solution ID #' . $solutionId . ' not found', 'solution_id');
        }

        return $solution;
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public static function getSolutionQuery(Request $request)
    {
        $solutionQuery = Solution::withReseller($request->user->resellerId);
        if (!$request->user->isAdmin) {
            $solutionQuery->where('ucs_reseller_active', 'Yes');
        }

        return $solutionQuery;
    }
}
