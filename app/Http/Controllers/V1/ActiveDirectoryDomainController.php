<?php

namespace App\Http\Controllers\V1;

use App\Models\V1\ActiveDirectoryDomain;
use UKFast\DB\Ditto\QueryTransformer;
use UKFast\Api\Resource\Traits\ResponseHelper;
use UKFast\Api\Resource\Traits\RequestHelper;
use Illuminate\Http\Request;

class ActiveDirectoryDomainController extends BaseController
{
    use ResponseHelper, RequestHelper;

    /**
     * List all domains
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $collectionQuery = ActiveDirectoryDomain::query();
        if (!empty($this->resellerId) || !$this->isAdmin) {
            $collectionQuery->where('ad_domain_reseller_id', $request->user->resellerId);
        }

        (new QueryTransformer($request))
            ->config(ActiveDirectoryDomain::class)
            ->transform($collectionQuery);

        return $this->respondCollection(
            $request,
            $collectionQuery->paginate($this->perPage)
        );
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public static function getDomainQuery(Request $request)
    {
        $podQuery = ActiveDirectoryDomain::query();
        if (!empty($request->user->resellerId)) {
            $podQuery->where('ad_domain_reseller_id', $request->user->resellerId);
        }

        return $podQuery;
    }

}
