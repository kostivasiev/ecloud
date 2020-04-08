<?php

namespace App\Http\Controllers\V1;

use Illuminate\Http\JsonResponse;
use UKFast\Api\Exceptions;
use App\Models\V1\PublicSupport;
use Illuminate\Http\Request;
use UKFast\DB\Ditto\QueryTransformer;
use UKFast\Api\Resource\Traits\ResponseHelper;
use UKFast\Api\Resource\Traits\RequestHelper;

class PublicSupportController extends BaseController
{
    use ResponseHelper, RequestHelper;

    /**
     * Display list of support resources
     *
     * @param Request $request
     * @param QueryTransformer $queryTransformer
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function index(Request $request, QueryTransformer $queryTransformer)
    {
        $collection = PublicSupport::query();

        $queryTransformer->config(PublicSupport::class)
            ->transform($collection);

        return $collection->paginate(
            $request->input('per_page', $this->perPage)
        );
    }

    /**
     * Display single support resource
     * @param Request $request
     * @param integer $id
     * @return JsonResponse
     * @throws Exceptions\NotFoundException
     */
    public function show(Request $request, $id)
    {
        $item = PublicSupport::find($id);
        if (!$item) {
            throw new Exceptions\NotFoundException('Support not found');
        }

        return response()->json([
            'data' => $item,
            'meta' => [],
        ]);
    }

    /**
     * Store new support resource
     * @param Request $request
     * @return JsonResponse
     * @throws Exceptions\UnauthorisedException
     */
    public function store(Request $request)
    {
        if (empty($request->user->resellerId)) {
            throw new Exceptions\UnauthorisedException('Unable to determine account id');
        }

        $item = new PublicSupport;
        $item->reseller_id = $request->user->resellerId;
        $item->save();

        return response()->json([
            'data' => [
                'id' => $item->getKey(),
            ],
            'meta' => [
                'location' => config('app.url') . '/v1/support/' . $item->getKey()
            ],
        ], 202);
    }
}
