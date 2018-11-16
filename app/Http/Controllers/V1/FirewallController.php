<?php

namespace App\Http\Controllers\V1;

use UKFast\DB\Ditto\QueryTransformer;

use UKFast\Api\Resource\Traits\ResponseHelper;
use UKFast\Api\Resource\Traits\RequestHelper;

use Illuminate\Http\Request;

use App\Services\NetworkingService;

use App\Models\V1\Firewall;
use App\Exceptions\V1\FirewallNotFoundException;

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
     * List all firewalls
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $collectionQuery = static::getFirewallQuery($request);

        (new QueryTransformer($request))
            ->config(Firewall::class)
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
     * @throws FirewallNotFoundException
     */
    public function show(Request $request, $solutionId)
    {
        return $this->respondItem(
            $request,
            static::getFirewallById($request, $solutionId)
        );
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
     * get solution by ID
     * @param Request $request
     * @param $firewallId
     * @return mixed
     * @throws FirewallNotFoundException
     */
    public static function getFirewallById(Request $request, $firewallId)
    {
        $firewall = static::getFirewallQuery($request)->find($firewallId);
        if (is_null($firewall)) {
            throw new FirewallNotFoundException('Firewall ID #' . $firewallId . ' not found', 'firewall_id');
        }

        return $firewall;
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public static function getFirewallQuery(Request $request)
    {
        $firewallQuery = Firewall::withReseller($request->user->resellerId)
            ->whereIn('servers_type', ['firewall', 'virtual firewall'])
            ->join('server_subtype', 'server_subtype_id', '=', 'servers_subtype_id')
            ->where('server_subtype_name', 'eCloud Dedicated');

        if (!$request->user->isAdmin) {
            $firewallQuery->where('servers_active', 'y');
        }

        return $firewallQuery;
    }
}
