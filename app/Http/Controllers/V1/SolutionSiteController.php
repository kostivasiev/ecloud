<?php

namespace App\Http\Controllers\V1;

use App\Exceptions\V1\SiteNotFoundException;
use App\Models\V1\SolutionSite;
use Illuminate\Http\Request;
use UKFast\Api\Resource\Traits\RequestHelper;
use UKFast\Api\Resource\Traits\ResponseHelper;
use UKFast\DB\Ditto\QueryTransformer;

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
        if (!$request->user()->isAdmin()) {
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
        $solutionSite = static::getSiteQuery($request)->find($siteId);
        if (is_null($solutionSite)) {
            throw new SiteNotFoundException('Site ID #' . $siteId . ' not found', 'site_id');
        }

        return $solutionSite;
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public static function getSiteQuery(Request $request)
    {
        $siteQuery = SolutionSite::withReseller($request->user()->resellerId());
        if (!$request->user()->isAdmin()) {
            $siteQuery->where('ucs_reseller_active', 'Yes');
        }

        return $siteQuery;
    }
}
