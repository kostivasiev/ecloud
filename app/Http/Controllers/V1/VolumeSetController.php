<?php

namespace App\Http\Controllers\V1;

use App\Datastore\Exceptions\DatastoreNotFoundException;
use App\Events\V1\VolumeSetIopsUpdatedEvent;
use App\Exceptions\V1\ArtisanException;
use App\Models\V1\IopsTier;
use App\Models\V1\San;
use App\Models\V1\Solution;
use App\Models\V1\Storage;
use App\Models\V1\VolumeSet;
use App\Rules\V1\IsValidUuid;
use App\Services\Artisan\V1\ArtisanService;
use Illuminate\Support\Facades\Event;
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
     * @throws \App\Exceptions\V1\PodNotFoundException
     * @throws \App\Exceptions\V1\SolutionNotFoundException
     * @throws \UKFast\Api\Resource\Exceptions\InvalidResourceException
     * @throws \UKFast\Api\Resource\Exceptions\InvalidResponseException
     * @throws \UKFast\Api\Resource\Exceptions\InvalidRouteException
     */
    public function create(Request $request)
    {
        $rules = VolumeSet::$rules;
        $rules = array_merge(
            $rules,
            [
                'solution_id' => ['required', 'integer'],
                'san_id' => ['required', 'integer'],
                'pod_id' => ['sometimes', 'integer']
            ]
        );
        $this->validate($request, $rules);

        $solution = SolutionController::getSolutionById($request, $request->input('solution_id'));

        $identifier = $this->getNextVolumeSetIdentifier($solution);

        $pod = ($request->has('pod_id')) ? PodController::getPodById($request, $request->input('pod_id')) : $solution->pod;

        $san = Storage::withPod($pod->getKey())->where('server_id', '=', $request->input('san_id'))->firstOrFail()->san;

        $artisan = app()->makeWith(
            ArtisanService::class,
            [
                [
                    'solution'=>$solution,
                    'san' => $san
                ]
            ]
        );

        $artisaResponse = $artisan->createVolumeSet($identifier);

        if (!$artisaResponse) {
            throw new ArtisanException('Failed to create volume set: ' . $artisan->getLastError());
        }

        $volumeSet = new VolumeSet;
        $volumeSet->name = $artisaResponse->name;
        $volumeSet->ucs_reseller_id = $request->input('solution_id');

        // return id & link to resource in meta
        $volumeSet->save();

        return $this->respondSave($request, $volumeSet, 201);
    }

    /**
     * Volumesets are of the format MCS_G0_VVSET_17106_(x),  where x is an increment for the solution.
     * Extracts the number and increments accordingly
     * @param Solution $solution
     * @return int|mixed
     */
    protected function getNextVolumeSetIdentifier(Solution $solution)
    {
        if ($solution->volumeSets->count() == 0) {
            return 1;
        }

        $identifiers = $solution->volumeSets()->get()->map(function ($item) {
            return ['name' => substr($item->name, strrpos($item->name, '_') + 1)];
        });

        return max($identifiers->pluck('name')->toArray()) + 1;
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
