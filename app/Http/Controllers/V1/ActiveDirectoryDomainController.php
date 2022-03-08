<?php

namespace App\Http\Controllers\V1;

use App\Models\V1\ActiveDirectoryDomain;
use Illuminate\Http\Request;
use UKFast\Api\Exceptions\NotFoundException;
use App\Services\V1\Resource\Traits\RequestHelper;
use App\Services\V1\Resource\Traits\ResponseHelper;
use UKFast\DB\Ditto\QueryTransformer;

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
        if ($request->user()->isScoped()) {
            $collectionQuery->where('ad_domain_reseller_id', $request->user()->resellerId());
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
     * Show specific solution
     *
     * @param Request $request
     * @param $domainId
     * @return \Illuminate\http\Response
     * @throws NotFoundException
     */
    public function show(Request $request, $domainId)
    {
        $domain = ActiveDirectoryDomain::withReseller($request->user()->resellerId())->find($domainId);
        if (is_null($domain)) {
            throw new NotFoundException(
                "An Active Directory domain matching the requested ID was not found"
            );
        }

        return $this->respondItem(
            $request,
            $domain
        );
    }
}
