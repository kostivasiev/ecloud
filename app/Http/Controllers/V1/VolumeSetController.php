<?php

namespace App\Http\Controllers\V1;

use App\Datastore\Exceptions\DatastoreNotFoundException;
use App\Events\V1\VolumeSetIopsUpdatedEvent;
use App\Exceptions\V1\ArtisanException;
use App\Exceptions\V1\SanNotFoundException;
use App\Models\V1\IopsTier;
use App\Models\V1\San;
use App\Models\V1\VolumeSet;
use App\Rules\V1\IsValidUuid;
use App\Services\Artisan\V1\ArtisanService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Event;
use UKFast\Api\Exceptions\NotFoundException;
use UKFast\Api\Exceptions\UnprocessableEntityException;
use App\Services\V1\Resource\Traits\RequestHelper;
use App\Services\V1\Resource\Traits\ResponseHelper;
use UKFast\DB\Ditto\QueryTransformer;

class VolumeSetController extends BaseController
{
    use ResponseHelper, RequestHelper;

    private static $model = VolumeSet::class;

    /**
     * Show collection
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request)
    {
        $collectionQuery = static::getQuery($request);

        (new QueryTransformer($request))
            ->config(self::$model)
            ->transform($collectionQuery);

        $items = $collectionQuery->paginate($this->perPage);

        return $this->respondCollection(
            $request,
            $items
        );
    }

    /**
     * Show item
     *
     * @param Request $request
     * @param $volumeSetId
     * @return Response
     */
    public function show(Request $request, $volumeSetId)
    {
        return $this->respondItem($request, static::getById($request, $volumeSetId));
    }


    /**
     * Create a volume set o the SAN
     * @param Request $request
     * @return Response
     * @throws ArtisanException
     * @throws SanNotFoundException
     * @throws UnprocessableEntityException
     * @throws \App\Exceptions\V1\SiteNotFoundException
     * @throws \App\Exceptions\V1\SolutionNotFoundException
     * @throws \Illuminate\Validation\ValidationException
     * @throws \App\Services\V1\Resource\Exceptions\InvalidResourceException
     * @throws \App\Services\V1\Resource\Exceptions\InvalidResponseException
     * @throws \App\Services\V1\Resource\Exceptions\InvalidRouteException
     */
    public function create(Request $request)
    {
        $rules = [
            'solution_id' => ['required_without:site_id', 'integer'],
            'site_id' => ['required_without:solution_id', 'integer'],
            'san_id' => ['sometimes', 'integer']
        ];
        $this->validate($request, $rules);

        // Determine the pod
        if ($request->has('site_id')) {
            $solutionSite = SolutionSiteController::getSiteById($request, $request->input('site_id'));
            $solution = $solutionSite->solution;
            $pod = $solutionSite->pod;
        }

        if ($request->has('solution_id')) {
            $solution = SolutionController::getSolutionById($request, $request->input('solution_id'));
            $pod = $solution->pod;
        }

        if ($pod->sans->count() == 0) {
            throw new SanNotFoundException('No SANS are available on the solution\'s pod');
        }

        if ($pod->sans->count() == 1) {
            $san = $pod->sans->first();
        }

        // If more than 1 san is available on the pod user must specify a san_id
        if ($pod->sans->count() > 1 && !$request->has('san_id')) {
            throw new UnprocessableEntityException(
                'More than one SAN is available on the solution\'s pod - Please specify a san_id'
            );
        }

        // If the user specified a san_id check that the san in on the solution / solution sites pod
        if ($request->has('san_id')) {
            $san = $pod->sans()->findOrFail($request->input('san_id'));
        }

        $identifier = VolumeSet::getNextIdentifier($solution);

        $artisan = app()->makeWith(
            'App\Services\Artisan\V1\ArtisanService',
            [['solution' => $solution, 'san' => $san]]
        );

        $artisanResponse = $artisan->createVolumeSet($identifier);

        if (!$artisanResponse) {
            throw new ArtisanException('Failed to create volume set: ' . $artisan->getLastError());
        }

        $volumeSet = new VolumeSet;
        $volumeSet->name = $artisanResponse->name;
        $volumeSet->ucs_reseller_id = $solution->getKey();

        // return id & link to resource in meta
        $volumeSet->save();

        return $this->respondSave($request, $volumeSet, 201);
    }

    /**
     * Set the max IOPS on a volume set.
     * We're using a numeric value here to allow custom setting of IOPS if the user is admin,
     * otherwise the value must match an IOPS tier
     *
     * @param Request $request
     * @param $volumeSetId
     * @return Response
     * @throws ArtisanException
     * @throws UnprocessableEntityException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function setIops(Request $request, $volumeSetId)
    {
        $rules = [
            'san_id' => ['required', 'integer'],
            'volume_set_id' => ['required', new IsValidUuid()],
            'max_iops' => ['required', 'integer']
        ];

        $request['volume_set_id'] = $volumeSetId;
        $this->validate($request, $rules);

        $volumeSet = static::getById($request, $volumeSetId);

        if (!$this->isAdmin) {
            // If the user is not admin ensure that max_iops corresponds to an IOPS tier
            IopsTier::where('max_iops', '=', $request->input('max_iops'))->firstOrFail();
        }

        $san = San::findOrFail($request->input('san_id'));

        if (!$san->storage()->withPod($volumeSet->solution->pod)->firstOrFail()->qosEnabled()) {
            throw new UnprocessableEntityException('Unable to configure IOPS: QoS is not available on the SAN');
        }

        $artisan = app()->makeWith(
            ArtisanService::class,
            [
                [
                    'solution' => $volumeSet->solution,
                    'san' => $san
                ]
            ]
        );

        $artisanResponse = $artisan->setIOPS($volumeSet->name, $request->input('max_iops'));

        if (!$artisanResponse) {
            $errorMessage = 'Failed to set IOPS for volume set.';
            $error = $artisan->getLastError();
            if (strpos($error, 'Invalid QOS target object') !== false) {
                $errorMessage .= ' The volume set is not valid.';
            }
            throw new ArtisanException($errorMessage . ' ' . $error);
        }

        $volumeSet->max_iops = $request->input('max_iops');
        $volumeSet->save();
        Event::dispatch(new VolumeSetIopsUpdatedEvent($volumeSet));

        return $this->respondEmpty();
    }

    /**
     * Add a datastore volume to a volumeset
     * @param Request $request
     * @param $volumeSetId
     * @return Response
     * @throws ArtisanException
     * @throws DatastoreNotFoundException
     */
    public function addDatastore(Request $request, $volumeSetId)
    {
        $rules = ['volume_set_id' => ['required', new IsValidUuid()]];
        $request['volume_set_id'] = $volumeSetId;
        $this->validate($request, $rules);

        $volumeSet = static::getById($request, $volumeSetId);

        $datastore = DatastoreController::getDatastoreById($request, $request->input('datastore_id'));

        $artisan = app()->makeWith(ArtisanService::class, [['datastore' => $datastore]]);

        $artisanResponse = $artisan->addVolumeToVolumeSet($volumeSet->name, $datastore->reseller_lun_name);

        if (!$artisanResponse) {
            throw new ArtisanException('Failed to add datastore to volume set: ' . $artisan->getLastError());
        }

        return $this->respondEmpty();
    }


    /**
     * Remove volume/datastore from volume set
     * @param Request $request
     * @param $volumeSetId
     * @param $datastoreId
     * @return Response
     * @throws ArtisanException
     * @throws DatastoreNotFoundException
     */
    public function removeDatastore(Request $request, $volumeSetId, $datastoreId)
    {
        $rules = ['volume_set_id' => ['required', new IsValidUuid()]];
        $request['volume_set_id'] = $volumeSetId;
        $this->validate($request, $rules);

        $volumeSet = static::getById($request, $volumeSetId);

        $datastore = DatastoreController::getDatastoreById($request, $datastoreId);

        $artisan = app()->makeWith(ArtisanService::class, [['datastore' => $datastore]]);

        $artisanResponse = $artisan->removeVolumeFromVolumeSet($volumeSet->name, $datastore->reseller_lun_name);

        if (!$artisanResponse) {
            throw new ArtisanException('Failed to remove datastore to volume set: ' . $artisan->getLastError());
        }

        return $this->respondEmpty();
    }


    /**
     * Export a volume set to a host set
     * @param Request $request
     * @param $volumeSetId
     * @return Response
     * @throws ArtisanException
     * @throws NotFoundException
     */
    public function export(Request $request, $volumeSetId)
    {
        $rules = [
            'volume_set_id' => ['required', new IsValidUuid()],
            'san_id' => ['required', 'numeric']
        ];

        $request['volume_set_id'] = $volumeSetId;
        $this->validate($request, $rules);
        $volumeSet = static::getById($request, $volumeSetId);

        $san = San::findOrFail($request->input('san_id'));

        // eCloud solutions should only have a single host set at this point, but this may change in the future.
        $hostSet = $volumeSet->solution->hostSets->first();

        if (!$hostSet) {
            throw new NotFoundException('No host set was found for solution ' . $volumeSet->solution->getKey());
        }

        $artisan = app()->makeWith(
            ArtisanService::class,
            [
                [
                    'solution' => $volumeSet->solution,
                    'san' => $san
                ]
            ]
        );

        $artisanResponse = $artisan->exportVolumeSet($volumeSet->name, $hostSet->name);

        if (!$artisanResponse) {
            throw new ArtisanException('Failed to export volume set to host set: ' . $artisan->getLastError());
        }

        return $this->respondEmpty();
    }


    /**
     * Delete volume set record.
     * @param Request $request
     * @param $volumeSetId
     * @return Response
     */
    public function delete(Request $request, $volumeSetId)
    {
        $rules = ['volume_set_id' => ['required', new IsValidUuid()]];
        $request['volume_set_id'] = $volumeSetId;
        $this->validate($request, $rules);

        $volumeSet = static::getById($request, $volumeSetId);

        $volumeSet->delete();

        return $this->respondEmpty();
    }


    /**
     * Delete a volume set from the SAN
     * @param Request $request
     * @param $volumeSetId
     * @return Response
     * @throws ArtisanException
     * @throws DatastoreNotFoundException
     */
    public function deleteVolumeSet(Request $request, $volumeSetId)
    {
        $rules = [
            'volume_set_id' => ['required', new IsValidUuid()],
            'datastore_id' => ['required', 'integer'],
        ];
        $request['volume_set_id'] = $volumeSetId;
        $this->validate($request, $rules);

        $volumeSet = static::getById($request, $volumeSetId);

        $datastore = DatastoreController::getDatastoreById($request, $request->input('datastore_id'));

        $artisan = app()->makeWith(ArtisanService::class, [['datastore' => $datastore]]);

        // Check if the volume set is empty
        $artisanResponse = $artisan->getVolumeSet($volumeSet->name);

        if (!$artisanResponse) {
            $error = $artisan->getLastError();
            throw new ArtisanException('Failed to load volume set: ' . $error);
        }

        $errorMessage = 'Failed to delete volume set: ';

        if (!empty($artisanResponse->volumes)) {
            throw new ArtisanException($errorMessage . 'The volume set is not empty.');
        }

        $artisanResponse = $artisan->deleteVolumeSet($volumeSet->name);

        if (!$artisanResponse) {
            $error = $artisan->getLastError();
            throw new ArtisanException($errorMessage . $error);
        }

        return $this->respondEmpty();
    }

    /**
     * @param Request $request
     * @param $volumeSetId
     * @return Response
     */
    public function volumes(Request $request, $volumeSetId)
    {
        $volumeSet = VolumeSet::find($volumeSetId);

        if (!$volumeSet || ($request->user()->isScoped() && $volumeSet->solution->reseller_id !== $request->user()->resellerId())) {
            return response([
                'errors' => [
                    'title' => 'Not found',
                    'detail' => 'No Volume Set with that ID was found',
                    'status' => 404,
                ]
            ], 404);
        }

        $sanVolumes = $volumeSet->volumes();
        if (count($sanVolumes) > 1) {
            abort(500, 'Same volume set found on more than one San');
        }

        $data = [];
        if (count($sanVolumes) == 1) {
            $data['volumes'] = array_shift($sanVolumes);
        }

        return response([
            'data' => $data,
            'meta' => [],
        ]);
    }

    /**
     * get item by ID
     * @param Request $request
     * @param $id
     * @return mixed
     */
    public static function getById(Request $request, $id)
    {
        return static::getQuery($request)->findorFail($id);
    }

    /**
     * Return a query builder for the model
     * @param Request $request
     * @return mixed
     */
    public static function getQuery(Request $request)
    {
        $query = self::$model::query();
        if ($request->user()->isScoped()) {
            $query->join(
                'ucs_reseller',
                (new self::$model)->getTable() . '.ucs_reseller_id',
                '=',
                'ucs_reseller.ucs_reseller_id'
            )
                ->where('ucs_reseller_reseller_id', '=', $request->user()->resellerId());
        }

        return $query;
    }
}
