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
            $collectionQuery->paginate($this->perPage)
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
        return $this->respondItem(
            $request,
            static::getDatastoreById($request, $datastoreId)
        );
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

    /**
     * @param Request $request
     * @return mixed
     */
    public static function getDatastoreQuery(Request $request)
    {
        $query = Datastore::query('reseller_lun_status', '!=', 'Deleted');
        if (!$request->user->isAdmin) {
//            $query->where('ucs_datacentre_active', 'Yes');
        }

        return $query;
    }
}
