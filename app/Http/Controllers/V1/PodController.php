<?php

namespace App\Http\Controllers\V1;

use UKFast\DB\Ditto\QueryTransformer;

use UKFast\Api\Resource\Traits\ResponseHelper;
use UKFast\Api\Resource\Traits\RequestHelper;

use Illuminate\Http\Request;

use App\Models\V1\Pod;
use App\Exceptions\V1\PodNotFoundException;

class PodController extends BaseController
{
    use ResponseHelper, RequestHelper;

    /**
     * List all solutions
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $collectionQuery = static::getPodQuery($request);

        (new QueryTransformer($request))
            ->config(Pod::class)
            ->transform($collectionQuery);

        $solutions = $collectionQuery->paginate($this->perPage);

        return $this->respondCollection(
            $request,
            $solutions
        );
    }

    /**
     * Show specific solution
     *
     * @param Request $request
     * @param $podId
     * @return \Illuminate\http\Response
     * @throws PodNotFoundException
     */
    public function show(Request $request, $podId)
    {
        return $this->respondItem(
            $request,
            static::getPodById($request, $podId)
        );
    }

    /**
     * get solution by ID
     * @param Request $request
     * @param $podId
     * @return mixed
     * @throws PodNotFoundException
     */
    public static function getPodById(Request $request, $podId)
    {
        $solution = static::getPodQuery($request)->find($podId);
        if (is_null($solution)) {
            throw new PodNotFoundException('Pod ID #' . $podId . ' not found');
        }

        return $solution;
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public static function getPodQuery(Request $request)
    {
        $podQuery = Pod::query();
        if (!$request->user->isAdmin) {
            $podQuery->where('ucs_datacentre_active', 'Yes');
        }

        return $podQuery;
    }
}
