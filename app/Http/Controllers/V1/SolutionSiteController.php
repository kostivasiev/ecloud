<?php

namespace App\Http\Controllers\V1;

use UKFast\DB\Ditto\QueryTransformer;

use UKFast\Api\Resource\Traits\ResponseHelper;
use UKFast\Api\Resource\Traits\RequestHelper;

use Illuminate\Http\Request;

use App\Models\V1\SolutionSite;
use App\Exceptions\V1\SiteNotFoundException;

class SolutionSiteController extends BaseController
{
    use ResponseHelper, RequestHelper;

    /**
     * List all sites
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $collectionQuery = static::getSiteQuery($request);
        if (!$request->user->isAdministrator) {
            $collectionQuery->where('ucs_reseller_active', 'Yes');
        };

        (new QueryTransformer($request))
            ->config(SolutionSite::class)
            ->transform($collectionQuery);

        return $this->respondCollection(
            $request,
            $collectionQuery->paginate($this->perPage)
        );
    }

    /**
     * Show specific site
     *
     * @param Request $request
     * @param $siteId
     * @return \Illuminate\http\Response
     * @throws SiteNotFoundException
     */
    public function show(Request $request, $siteId)
    {
        return $this->respondItem(
            $request,
            static::getSiteById($request, $siteId)
        );
    }

    /**
     * List Solution Sites
     * @param Request $request
     * @param $solutionId
     * @return \Illuminate\Http\Response
     * @throws \App\Exceptions\V1\SolutionNotFoundException
     */
    public function getSolutionSites(Request $request, $solutionId)
    {
        SolutionController::getSolutionById($request, $solutionId);
        $collectionQuery = SolutionSite::withSolution($solutionId);

        (new QueryTransformer($request))
            ->config(SolutionSite::class)
            ->transform($collectionQuery);

        return $this->respondCollection(
            $request,
            $collectionQuery->paginate($this->perPage)
        );
    }

    /**
     * get solution by ID
     * @param Request $request
     * @param $siteId
     * @return mixed
     * @throws SiteNotFoundException
     */
    public static function getSiteById(Request $request, $siteId)
    {
        $solution = static::getSiteQuery($request)->find($siteId);
        if (is_null($solution)) {
            throw new SiteNotFoundException('Solution ID #' . $siteId . ' not found', 'solution_id');
        }

        return $solution;
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public static function getSiteQuery(Request $request)
    {
        $solutionQuery = SolutionSite::withReseller($request->user->resellerId);
        if (!$request->user->isAdministrator) {
            $solutionQuery->where('ucs_reseller_active', 'Yes');
        }

        return $solutionQuery;
    }
}
