<?php

namespace App\Http\Controllers\V1;

use UKFast\DB\Ditto\QueryTransformer;

use UKFast\Api\Resource\Traits\ResponseHelper;
use UKFast\Api\Resource\Traits\RequestHelper;

use Illuminate\Http\Request;

use App\Services\NetworkingService;

use App\Models\V1\Firewall;

class FirewallController extends BaseController
{
    use ResponseHelper, RequestHelper;

    protected $networkingService;

    public function __construct(Request $request, NetworkingService $networkingService)
    {
        parent::__construct($request);
        $this->networkingService = $networkingService;
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getSolutionFirewalls(Request $request, $solutionId)
    {
        //$firewalls = $this->networkingService->getSolutionFirewalls($request, $solutionId);

        $collectionQuery = static::getFirewallQuery($request)
            ->withSolution($solutionId);

        if (!$request->user->isAdmin) {
            $collectionQuery->where('servers_active', 'y');
        }

        (new QueryTransformer($request))
            ->config(Firewall::class)
            ->transform($collectionQuery);

        $firewalls = $collectionQuery->paginate($this->perPage);

        return $this->respondCollection(
            $request,
            $firewalls
        );
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public static function getFirewallQuery(Request $request)
    {
        $solutionQuery = Firewall::withReseller($request->user->resellerId)
            ->whereIn('servers_type', ['firewall', 'virtual firewall'])
            ->join('server_subtype', 'server_subtype_id', '=', 'servers_subtype_id')
            ->where('server_subtype_name', 'eCloud Dedicated');

        if (!$request->user->isAdmin) {
            $solutionQuery->where('ucs_reseller_active', 'Yes');
        }

        return $solutionQuery;
    }
}
