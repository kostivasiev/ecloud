<?php

namespace App\Http\Controllers\V1;

use UKFast\Api\Exceptions\ApiException;
use UKFast\DB\Ditto\QueryTransformer;

use UKFast\Api\Resource\Traits\ResponseHelper;
use UKFast\Api\Resource\Traits\RequestHelper;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Services\NetworkingService;
use App\Exceptions\V1\NetworkingServiceException;

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
     * @param $firewallId
     * @return \Illuminate\http\Response
     * @throws FirewallNotFoundException
     */
    public function show(Request $request, $firewallId)
    {
        return $this->respondItem(
            $request,
            static::getFirewallById($request, $firewallId)
        );
    }

    /**
     * @param Request $request
     * @param $solutionId
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
     * @param Request $request
     * @param $firewallId
     * @return Response
     * @throws FirewallNotFoundException
     * @throws NetworkingServiceException
     */
    public function getFirewallConfig(Request $request, $firewallId)
    {
        // verify fw owner until networking service supports it
        static::getFirewallById($request, $firewallId);

        try {
            $this->networkingService->scopeResellerID($request->user->resellerId);
            $firewallConfig = $this->networkingService->getFirewallConfig($firewallId);
        } catch (NetworkingServiceException $exception) {
            if ($exception->statusCode == 404) {
                throw new FirewallNotFoundException('Firewall ID #' . $firewallId . ' not found', 'firewall_id');
            }

            throw $exception;
        }

        return new Response([
            'data' => (object) [
                'config' => base64_encode($firewallConfig)
            ],
            'meta' => (object) []
        ], 200);
    }


    /**
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
