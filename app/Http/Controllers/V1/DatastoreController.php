<?php

namespace App\Http\Controllers\V1;

use UKFast\DB\Ditto\QueryTransformer;

use UKFast\Api\Resource\Traits\ResponseHelper;
use UKFast\Api\Resource\Traits\RequestHelper;

use Illuminate\Http\Request;

use App\Models\V1\Datastore;
use App\Resources\V1\DatastoreResource;
use App\Exceptions\V1\DatastoreNotFoundException;

class DatastoreController extends BaseController
{
    use ResponseHelper, RequestHelper;

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
        $datastore->getUsage();

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
     * @param Request $request
     * @return mixed
     */
    public static function getDatastoreQuery(Request $request)
    {
        $query = Datastore::query()
            ->where('reseller_lun_status', '!=', 'Deleted')
            ->join('ucs_reseller', 'ucs_reseller_id', '=', 'reseller_lun_ucs_reseller_id');

        if (!$request->user->isAdmin) {
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
            throw new DatastoreNotFoundException('Pod ID #' . $datastoreId . ' not found');
        }

        return $datastore;
    }
}
