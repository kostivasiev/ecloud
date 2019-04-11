<?php

namespace App\Http\Controllers\V1;

use UKFast\DB\Ditto\QueryTransformer;

use UKFast\Api\Resource\Traits\ResponseHelper;
use UKFast\Api\Resource\Traits\RequestHelper;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Services\IntapiService;
use App\Exceptions\V1\IntapiServiceException;
use App\Exceptions\V1\ServiceUnavailableException;

use App\Models\V1\Firewall;
use App\Exceptions\V1\FirewallNotFoundException;

class FirewallController extends BaseController
{
    use ResponseHelper, RequestHelper;

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
     * @param IntapiService $intapiService
     * @param $firewallId
     * @return Response
     * @throws FirewallNotFoundException
     * @throws ServiceUnavailableException
     */
    public function getFirewallConfig(Request $request, IntapiService $intapiService, $firewallId)
    {
        static::getFirewallById($request, $firewallId);

        try {
            $firewallConfig = $intapiService->getFirewallConfig($firewallId);
        } catch (IntapiServiceException $exception) {
            throw new ServiceUnavailableException(
                'Firewall ID #' . $firewallId . ' config temporarily unavailable'
            );
        }

        return new Response([
            'data' => (object) [
                'config' => $firewallConfig
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

        if (!$request->user->isAdministrator) {
            $firewallQuery->where('servers_active', 'y');
        }

        return $firewallQuery;
    }
}
