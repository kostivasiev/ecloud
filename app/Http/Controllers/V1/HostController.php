<?php

namespace App\Http\Controllers\V1;

use App\Exceptions\V1\ArtisanException;
use App\Exceptions\V1\IntapiServiceException;
use App\Exceptions\V1\KingpinException;
use App\Exceptions\V1\ServiceUnavailableException;
use App\Services\Artisan\V1\ArtisanService;
use App\Services\IntapiService;
use UKFast\Api\Exceptions\BadRequestException;
use UKFast\DB\Ditto\QueryTransformer;

use UKFast\Api\Resource\Traits\ResponseHelper;
use UKFast\Api\Resource\Traits\RequestHelper;

use Illuminate\Http\Request;
use Log;

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

        $fcwwns = $host->getFCWWNs();

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
     * Fire off automation to delete a host
     * @param Request $request
     * @param IntapiService $intapiService
     * @param $hostId
     * @return \Illuminate\Http\Response
     * @throws HostNotFoundException
     * @throws ServiceUnavailableException
     */
    public function delete(Request $request, IntapiService $intapiService, $hostId)
    {
        $host = static::getHostById($request, $hostId);

        if ($host->solution->hostSets()->count() != 1) {
            Log::error(
                'Unable to determine host set for host',
                [
                    'host_id' => $hostId,
                    'solution_id' => $host->solution->getKey()
                ]
            );
            throw new ServiceUnavailableException('Unable to delete host at this time');
        }

        // eCloud solutions should only have a single host set
        $hostSet = $host->solution->hostSets()->first();

        try {
            $automationRequestId = $intapiService->automationRequest(
                'expand_lun',
                'reseller_lun',
                $host->getKey(),
                ['host_set_id' => $hostSet->getKey()],
                'ecloud_ucs_' . $host->pod->getKey(),
                $request->user->id,
                $request->user->type
            );
        } catch (IntapiServiceException $exception) {
            throw new ServiceUnavailableException('Failed to expand datastore.');
        }

        $headers = [];
        if ($request->user->isAdministrator) {
            $headers = [
                'X-AutomationRequestId' => $automationRequestId
            ];
        }

        return $this->respondEmpty(202, $headers);
    }


    /**
     * Delete a host from the SAN (actually all SAN's associated with the Pod for the reseller's solution)
     * @param Request $request
     * @param $hostId
     * @return \Illuminate\Http\Response
     * @throws BadRequestException
     * @throws HostNotFoundException
     */
    public function deleteHost(Request $request, $hostId)
    {
        $host = static::getHostById($request, $hostId);

        if (empty($host->ucs_node_internal_name)) {
            throw new BadRequestException('Invalid host record: Missing host internal name');
        }

        // Loop over all the sans for the solutions pod and delete the host on all SANs
        $solution = $host->solution;
        $solution->pod->sans->each(function ($san) use ($host, $solution) {
            $artisan = app()->makeWith(ArtisanService::class, [['solution'=>$solution, 'san' => $san]]);

            // Delete host on san
            $artisanResponse = $artisan->removeHost($host->ucs_node_internal_name);

            if (!$artisanResponse) {
                Log::error(
                    'Failed to delete Host from SAN',
                    [
                        'san_id' => $san->id
                    ]
                );
                throw new ArtisanException('Failed to delete Host: ' . $artisan->getLastError());
            }
        });

        return $this->respondEmpty();
    }

    /**
     * Rescan the host's cluster on VMWare
     * @param Request $request
     * @param $hostId
     * @return \Illuminate\Http\Response
     * @throws HostNotFoundException
     * @throws KingpinException
     */
    public function clusterRescan(Request $request, $hostId)
    {
        $host = static::getHostById($request, $hostId);

        try {
            $host->clusterRescan();
        } catch (\Exception $exception) {
            throw new KingpinException('Failed to rescan host cluster: ' . $exception->getMessage());
        }

        return $this->respondEmpty();
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
