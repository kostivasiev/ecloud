<?php

namespace App\Http\Controllers\V1;

use App\Models\V1\IopsTier;
use Illuminate\Http\Request;
use App\Services\V1\Resource\Traits\RequestHelper;
use App\Services\V1\Resource\Traits\ResponseHelper;
use UKFast\DB\Ditto\QueryTransformer;

class IOPSController extends BaseController
{
    use ResponseHelper, RequestHelper;

    private static $model = IopsTier::class;

    /**
     * Show collection
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $collectionQuery = static::getQuery();

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
        return $this->respondItem($request, static::getById($id));
    }

    /**
     * get item by ID
     * @param $id
     * @return mixed
     */
    public static function getById($id)
    {
        return static::getQuery()->findOrFail($id);
    }

    /**
     * Return a query builder for the model
     * @return mixed
     */
    public static function getQuery()
    {
        return self::$model::query();
    }
}
