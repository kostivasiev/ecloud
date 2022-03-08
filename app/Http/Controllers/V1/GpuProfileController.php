<?php

namespace App\Http\Controllers\V1;

use App\Models\V1\GpuProfile;
use Illuminate\Http\Request;
use App\Services\V1\Resource\Traits\RequestHelper;
use App\Services\V1\Resource\Traits\ResponseHelper;
use UKFast\DB\Ditto\QueryTransformer;

class GpuProfileController extends BaseController
{
    use ResponseHelper, RequestHelper;

    private static $model = GpuProfile::class;

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
            $items,
            200,
            null,
            [],
            ($this->isAdmin) ? null : GpuProfile::VISIBLE_SCOPE_RESELLER
        );
    }

    /**
     * Show item
     *
     * @param Request $request
     * @param $profileId
     * @return \Illuminate\http\Response
     */
    public function show(Request $request, $profileId)
    {
        return $this->respondItem(
            $request,
            static::getById($request, $profileId),
            200,
            null,
            [],
            ($this->isAdmin) ? null : GpuProfile::VISIBLE_SCOPE_RESELLER
        );
    }

    /**
     * Get item by ID
     * @param Request $request
     * @param $id
     * @return mixed
     */
    public static function getById(Request $request, $id)
    {
        $object = static::getQuery($request)->findOrFail($id);

        return $object;
    }

    /**
     * Return a query builder for the model
     * @return mixed
     */
    public static function getQuery()
    {
        return self::$model::query();
    }

    /**
     * Calculates available GPU resources in pool for assigning to VM's
     * @return int
     */
}
