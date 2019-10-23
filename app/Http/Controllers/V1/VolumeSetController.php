<?php

namespace App\Http\Controllers\V1;

use App\Datastore\Exceptions\DatastoreNotFoundException;
use App\Events\V1\VolumeSetIopsUpdatedEvent;
use App\Exceptions\V1\ArtisanException;
use App\Models\V1\IopsTier;
use App\Models\V1\San;
use App\Models\V1\VolumeSet;
use App\Rules\V1\IsValidUuid;
use App\Services\Artisan\V1\ArtisanService;
use Illuminate\Support\Facades\Event;
use UKFast\Api\Exceptions\BadRequestException;
use UKFast\Api\Exceptions\NotFoundException;
use UKFast\Api\Exceptions\UnprocessableEntityException;
use UKFast\DB\Ditto\QueryTransformer;

use UKFast\Api\Resource\Traits\ResponseHelper;
use UKFast\Api\Resource\Traits\RequestHelper;

use Illuminate\Http\Request;

class VolumeSetController extends BaseController
{
    use ResponseHelper, RequestHelper;

    private static $model = VolumeSet::class;

    /**
     * Show collection
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
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
     * @param $id
     * @return \Illuminate\http\Response
     */
    public function show(Request $request, $id)
    {
        return $this->respondItem($request, static::getById($request, $id));
    }


    /**
     * Create a volume set o the SAN
     * @param Request $request
     * @return \Illuminate\Http\Response
     * @throws ArtisanException
     * @throws DatastoreNotFoundException
     * @throws UnprocessableEntityException
     * @throws \App\Exceptions\V1\SolutionNotFoundException
     * @throws \UKFast\Api\Resource\Exceptions\InvalidResourceException
     * @throws \UKFast\Api\Resource\Exceptions\InvalidResponseException
     * @throws \UKFast\Api\Resource\Exceptions\InvalidRouteException
     */
    public function create(Request $request)
    {
        $rules = [
            'datastore_id' => ['required', 'numeric']
        ];

        $this->validate($request, $rules);

        $datastore = DatastoreController::getDatastoreById($request, $request->input('datastore_id'));

        // If the datastore record is not mapped to a volume on the SAN, fail, as we can't match the volume set name to
        // the volume name.
        if (empty($datastore->reseller_lun_name)) {
            throw new UnprocessableEntityException('Unable to determine datastore name');
        }

        // If the volume has been created by the APIO it should be of the format MCS_G0_VV_17106_DATA_2
        if (!preg_match('/\w+[DATA|CLUSTER|QRM]_(\d+)+/', $datastore->reseller_lun_name, $matches) == true) {
            throw new UnprocessableEntityException('Invalid datastore name');
        }

        $identifier = (int) $matches[1];

        $artisan = app()->makeWith('App\Services\Artisan\V1\ArtisanService', [['datastore' => $datastore]]);

        $artisanResponse = $artisan->createVolumeSet($identifier);

        if (!$artisanResponse) {
            throw new ArtisanException('Failed to create volume set: ' . $artisan->getLastError());
        }

        $volumeSet = new VolumeSet;
        $volumeSet->name = $artisanResponse->name;
        $volumeSet->ucs_reseller_id = $datastore->reseller_lun_ucs_reseller_id;

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
     * @return \Illuminate\Http\Response
     * @throws ArtisanException
     * @throws UnprocessableEntityException
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

        if (!$san->storage->qosEnabled()) {
            throw new UnprocessableEntityException('Unable to configure IOPS: QoS is not available on the SAN');
        }

        $artisan = app()->makeWith(
            ArtisanService::class,
            [
                [
                    'solution'=>$volumeSet->solution,
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
            throw new ArtisanException($errorMessage. ' ' . $error);
        }

        $volumeSet->max_iops = $request->input('max_iops');
        $volumeSet->save();
        Event::fire(new VolumeSetIopsUpdatedEvent($volumeSet));

        return $this->respondEmpty();
    }

    /**
     * Add a datastore volume to a volumeset
     * @param Request $request
     * @param $volumeSetId
     * @return \Illuminate\Http\Response
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
     * @return \Illuminate\Http\Response
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
     * @return \Illuminate\Http\Response
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
                    'solution'=>$volumeSet->solution,
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
     * Delete volume set record. Check the SAN to see if the volume set contains any volumes to
     * prevent delete of non empty volume sets.
     * @param Request $request
     * @param $volumeSetId
     * @return \Illuminate\Http\Response
     */
    public function delete(Request $request, $volumeSetId)
    {
        $rules = ['volume_set_id' => ['required', new IsValidUuid()]];
        $request['volume_set_id'] = $volumeSetId;
        $this->validate($request, $rules);

        $volumeSet = static::getById($request, $volumeSetId);

        $solution = $volumeSet->solution;
        // Check if the volume set is empty, as we cant target a specific san we'll need to check all the sans for the solutions pod
        $solution->pod->sans->each(function ($san) use ($solution, $volumeSet) {
            $artisan = app()->makeWith(ArtisanService::class, [['solution'=>$solution, 'san' => $san]]);

            $artisanResponse = $artisan->getVolumeSet($volumeSet->name);

            if (!$artisanResponse) {
                $error = $artisan->getLastError();
                if (strpos($error, 'Set does not exist') !== false) {
                    return true; //continue to the next san
                }

                throw new ArtisanException('Failed to load volume set: ' . $error);
            }

            if (!empty($artisanResponse->volumes)) {
                throw new ArtisanException('Failed to delete volume set. The volume set is not empty.');
            }
        });

        $volumeSet->delete();

        return $this->respondEmpty();
    }


    /**
     * Delete a volume set from the SAN
     * @param Request $request
     * @param $volumeSetId
     * @return \Illuminate\Http\Response
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

        $errorMessage = 'Failed to delete volume set: ' ;

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
        if ($request->user->resellerId != 0) {
            $query->join('ucs_reseller', (new self::$model)->getTable() . '.ucs_reseller_id', '=', 'ucs_reseller.ucs_reseller_id')
            ->where('ucs_reseller_reseller_id', '=', $request->user->resellerId);
        }

        return $query;
    }
}
