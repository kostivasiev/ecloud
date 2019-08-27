<?php

namespace App\Http\Controllers\V1;

use App\Datastore\Status;
use App\Exceptions\V1\ArtisanException;
use App\Exceptions\V1\IntapiServiceException;
use App\Exceptions\V1\KingpinException;
use App\Services\IntapiService;
use App\Traits\V1\SanitiseRequestData;
use UKFast\Api\Exceptions\ForbiddenException;
use UKFast\DB\Ditto\QueryTransformer;

use UKFast\Api\Resource\Traits\ResponseHelper;
use UKFast\Api\Resource\Traits\RequestHelper;

use Illuminate\Http\Request;

use App\Models\V1\Datastore;
use App\Resources\V1\DatastoreResource;
use App\Datastore\Exceptions\DatastoreNotFoundException;

class DatastoreController extends BaseController
{
    use ResponseHelper, RequestHelper, SanitiseRequestData;

    /**
     * List all Datastores
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $collectionQuery = static::getDatastoreQuery($request);

        (new QueryTransformer($request))
            ->config(Datastore::class)
            ->transform($collectionQuery);

        return $this->respondCollection(
            $request,
            $collectionQuery->paginate($this->perPage),
            200,
            DatastoreResource::class,
            [],
            Datastore::$collectionProperties
        );
    }

    /**
     * Show specific datastore
     *
     * @param Request $request
     * @param $datastoreId
     * @return \Illuminate\http\Response
     * @throws DatastoreNotFoundException
     */
    public function show(Request $request, $datastoreId)
    {
        $datastore = static::getDatastoreById($request, $datastoreId);

        return $this->respondItem(
            $request,
            $datastore,
            200,
            DatastoreResource::class,
            [],
            Datastore::$itemProperties
        );
    }

    /**
     * Update datastore
     * @param Request $request
     * @param $datastoreId
     * @return \Illuminate\Http\Response
     * @throws DatastoreNotFoundException
     */
    public function update(Request $request, $datastoreId)
    {
        $datastore = static::getDatastoreById($request, $datastoreId);

        $rules = Datastore::getRules();

        $rules = array_merge(
            $rules,
            [
                'capacity' => ['nullable', 'numeric'],
            ]
        );

        // Only allow status to be updated at this time
        $this->sanitiseRequestData($request, ['status', 'capacity']);

        $request['id'] = $datastoreId;
        $this->validate($request, $rules);

        $datastore = $this->receiveItem($request, Datastore::class);

        $datastore->resource->save();

        return $this->respondEmpty();
    }

    /**
     * Expand a datastore - Initiate automation to expand a datastore
     *
     * Schedules automation to expand a datastore
     * @param Request $request
     * @param IntapiService $intapiService
     * @param $datastoreId
     * @return \Illuminate\Http\Response
     * @throws ArtisanException
     * @throws DatastoreNotFoundException
     * @throws ForbiddenException
     * Todo: This is locked down to admin until we move billing from myukfast to an automation step for expand datastore
     */
    public function expand(Request $request, IntapiService $intapiService, $datastoreId)
    {
        $this->validate($request, ['size_gb' => 'required|integer|min:2']);

        $datastore = DatastoreController::getDatastoreById($request, $datastoreId);

        // check the new size is larger than the current size
        $newSizeGB = $request->input('size_gb');
        if ($newSizeGB <= $datastore->reseller_lun_size_gb) {
            throw new ForbiddenException('New datastore size must be greater than the current size');
        }
        $datastore->reseller_lun_status = Status::EXPANDING;

        if ($datastore->reseller_lun_lun_type != 'DATA') {
            throw new ForbiddenException(
                'Datastores of type ' . $datastore->reseller_lun_lun_type . ' can not be expanded automatically'
            );
        }

        try {
            $automationRequestId = $intapiService->automationRequest(
                'expand_lun',
                'reseller_lun',
                $datastore->getKey(),
                [
                    'new_capacity_gb' => $newSizeGB
                ],
                'ecloud_ucs_' . $datastore->storage->pod->getKey(),
                $request->user->applicationId
            );
        } catch (IntapiServiceException $exception) {
            throw new ArtisanException('Failed to expand datastore: ' . $exception->getMessage());
        }

        $datastore->save();

        $headers = [];
        if ($request->user->isAdministrator) {
            $headers = [
                'X-AutomationRequestId' => $automationRequestId
            ];
        }

        return $this->respondEmpty(202, $headers);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public static function getDatastoreQuery(Request $request)
    {
        $query = Datastore::withReseller($request->user->resellerId)
            ->where('reseller_lun_status', '!=', 'Deleted')
            ->join('ucs_reseller', 'ucs_reseller_id', '=', 'reseller_lun_ucs_reseller_id');

        if (!$request->user->isAdministrator) {
            $query->where('ucs_reseller_active', 'Yes');
        }

        return $query;
    }

    /**
     * get datastore by ID
     * @param Request $request
     * @param $datastoreId
     * @return mixed
     * @throws DatastoreNotFoundException
     */
    public static function getDatastoreById(Request $request, $datastoreId)
    {
        $datastore = static::getDatastoreQuery($request)->find($datastoreId);

        if (is_null($datastore)) {
            throw new DatastoreNotFoundException('Datastore ID #' . $datastoreId . ' not found');
        }

        return $datastore;
    }

    /**
     * List Solution Datastores
     * @param Request $request
     * @param $solutionId
     * @return \Illuminate\Http\Response
     * @throws \App\Exceptions\V1\SolutionNotFoundException
     */
    public function indexSolution(Request $request, $solutionId)
    {
        SolutionController::getSolutionById($request, $solutionId);
        $collectionQuery = static::getDatastoreQuery($request)
            ->withSolution($solutionId);

        (new QueryTransformer($request))
            ->config(Datastore::class)
            ->transform($collectionQuery);

        return $this->respondCollection(
            $request,
            $collectionQuery->paginate($this->perPage),
            200,
            DatastoreResource::class,
            [],
            Datastore::$collectionProperties
        );
    }

    /**
     * Expand the datastore volume on the SAN
     * @param Request $request
     * @param $datastoreId
     * @return \Illuminate\Http\Response
     * @throws DatastoreNotFoundException
     * @throws ForbiddenException
     */
    public function expandVolume(Request $request, $datastoreId)
    {
        $datastore = DatastoreController::getDatastoreById($request, $datastoreId);

        $this->validate($request, ['size_gb' => 'required|integer|min:2']);

        // check the new size is larger than the current size
        $newSizeGB = $request->input('size_gb');
        if ($newSizeGB <= $datastore->reseller_lun_size_gb) {
            throw new ForbiddenException('New datastore size must be greater than the current size');
        }

        // Convert GB to Mib
        $newSizeMiB = $newSizeGB * 1024;

        $datastore->expandVolume($newSizeMiB);

        return $this->respondEmpty();
    }


    /**
     * Rescan a cluster on VMWare after expanding a datastore
     * @param Request $request
     * @param $datastoreId
     * @return \Illuminate\Http\Response
     * @throws DatastoreNotFoundException
     * @throws KingpinException
     */
    public function clusterRescan(Request $request, $datastoreId)
    {
        $datastore = DatastoreController::getDatastoreById($request, $datastoreId);

        try {
            $datastore->clusterRescan();
        } catch (\Exception $exception) {
            throw new KingpinException('Failed to rescan datastore: ' . $exception->getMessage());
        }

        return $this->respondEmpty();
    }

    /**
     * Expand the datastore
     * @param Request $request
     * @param $datastoreId
     * @return \Illuminate\Http\Response
     * @throws DatastoreNotFoundException
     * @throws KingpinException
     */
    public function expandDatastore(Request $request, $datastoreId)
    {
        $datastore = DatastoreController::getDatastoreById($request, $datastoreId);

        try {
            $datastore->expand();
        } catch (\Exception $exception) {
            throw new KingpinException('Failed to expand datastore: ' . $exception->getMessage());
        }

        return $this->respondEmpty();
    }
}
