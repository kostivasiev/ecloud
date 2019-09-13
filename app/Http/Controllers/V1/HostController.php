<?php

namespace App\Http\Controllers\V1;

use App\Exceptions\V1\ArtisanException;
use App\Services\Artisan\V1\ArtisanService;
use UKFast\Api\Exceptions\BadRequestException;
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
     * Create Host
     *
     * For creating hosts we need to create the host on all SAN's associated with the Pod for the reseller's solution
     * as we don't have a way of targeting a specific SAN, and Hosts can have storage from different SAN's.
     *
     * @param Request $request
     * @param $hostId
     * @return \Illuminate\Http\Response
     * @throws BadRequestException
     * @throws HostNotFoundException
     */
    public function createHost(Request $request, $hostId)
    {
        $host = static::getHostById($request, $hostId);

        if (!empty($host->ucs_node_internal_name)) {
            throw new BadRequestException('A host has already been assigned to this record.');
        }

        $fcwwns = [];
        for ($i = 0; $i < 4; $i++) {
            $wwn = 'ucs_node_fc'. $i .'_wwpn';
            if (!empty($host->$wwn)) {
                $fcwwns[] = $host->$wwn;
            }
        }

        // Loop over all the sans for the solutions pod and create the host on all sans
        $hostInternalName = null;
        $solution = $host->solution;
        $solution->pod->sans->each(function ($san) use ($host, $solution, $fcwwns, &$hostInternalName) {
            $artisan = app()->makeWith(ArtisanService::class, [['solution'=>$solution, 'san' => $san]]);

            // Create host on san
            $artisanResponse = $artisan->createHost($host->getKey(), $fcwwns);

            if (!$artisanResponse) {
                throw new ArtisanException('Failed to create Host: ' . $artisan->getLastError());
            }
            $hostInternalName = $artisanResponse->name;
        });

        $host->ucs_node_internal_name = $hostInternalName;
        $host->save();

        return $this->respondEmpty(201);
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
