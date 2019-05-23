<?php

namespace App\Http\Controllers\V1;

use UKFast\DB\Ditto\QueryTransformer;

use UKFast\Api\Resource\Traits\ResponseHelper;
use UKFast\Api\Resource\Traits\RequestHelper;

use Illuminate\Http\Request;

use App\Models\V1\Host;
use App\Resources\V1\HostResource;
use App\Exceptions\V1\HostNotFoundException;

class HostController extends BaseController
{
    use ResponseHelper, RequestHelper;

    /**
     * List Collection
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $collectionQuery = static::getHostQuery($request);

        (new QueryTransformer($request))
            ->config(Host::class)
            ->transform($collectionQuery);

        return $this->respondCollection(
            $request,
            $collectionQuery->paginate($this->perPage)
        );
    }

    /**
     * Show specific item
     *
     * @param Request $request
     * @param $hostId
     * @return \Illuminate\http\Response
     * @throws HostNotFoundException
     */
    public function show(Request $request, $hostId)
    {
        $host = static::getHostById($request, $hostId);
        $host->getVmwareUsage();

        return $this->respondItem(
            $request,
            $host,
            200,
            HostResource::class
        );
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public static function getHostQuery(Request $request)
    {
        $query = Host::withReseller($request->user->resellerId)
            ->where('ucs_node_status', '!=', 'Cancelled')
            ->join('ucs_reseller', 'ucs_reseller_id', '=', 'ucs_node_ucs_reseller_id')

            ->join('ucs_specification', 'ucs_specification_id', '=', 'ucs_node_specification_id')
            ->where('ucs_specification_active', '=', 'Yes')
        ;

        if (!$request->user->isAdministrator) {
            $query->where('ucs_reseller_active', 'Yes');
        }

        return $query;
    }

    /**
     * get host by ID
     * @param Request $request
     * @param $hostId
     * @return mixed
     * @throws HostNotFoundException
     */
    public static function getHostById(Request $request, $hostId)
    {
        $host = static::getHostQuery($request)->find($hostId);

        if (is_null($host)) {
            throw new HostNotFoundException('Host ID #' . $hostId . ' not found');
        }

        return $host;
    }

    /**
     * List Solution Hosts
     * @param Request $request
     * @param $solutionId
     * @return \Illuminate\Http\Response
     * @throws \App\Exceptions\V1\SolutionNotFoundException
     */
    public function indexSolution(Request $request, $solutionId)
    {
        SolutionController::getSolutionById($request, $solutionId);

        $collectionQuery = static::getHostQuery($request)
            ->withSolution($solutionId);

        (new QueryTransformer($request))
            ->config(Host::class)
            ->transform($collectionQuery);

        return $this->respondCollection(
            $request,
            $collectionQuery->paginate($this->perPage)
        );
    }
}
