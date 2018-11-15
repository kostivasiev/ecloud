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

        $collectionQuery = Firewall::withReseller($request->user->resellerId)
            ->whereIn('servers_type', ['firewall', 'virtual firewall'])
            ->withSolution($solutionId);

        (new QueryTransformer($request))
            ->config(Firewall::class)
            ->transform($collectionQuery);

        $firewalls = $collectionQuery->paginate($this->perPage);

        return $this->respondCollection(
            $request,
            $firewalls
        );
    }
}
