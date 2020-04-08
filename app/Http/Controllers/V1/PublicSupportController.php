<?php

namespace App\Http\Controllers\V1;

use Illuminate\Http\JsonResponse;
use UKFast\Api\Exceptions\NotFoundException;
use App\Models\V1\PublicSupport;
use Illuminate\Http\Request;
use UKFast\DB\Ditto\QueryTransformer;
use UKFast\Api\Resource\Traits\ResponseHelper;
use UKFast\Api\Resource\Traits\RequestHelper;

class PublicSupportController extends BaseController
{
    use ResponseHelper, RequestHelper;

    /**
     * List all Public Support Customers
     *
     * @param Request $request
     * @param QueryTransformer $queryTransformer
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function index(Request $request, QueryTransformer $queryTransformer)
    {
        $supportClients = PublicSupport::query();

        $queryTransformer->config(PublicSupport::class)
            ->transform($supportClients);

        return $supportClients->paginate(
            $request->input('per_page', $this->perPage)
        );
    }

    /**
     * Display single Support Customer
     * @param Request $request
     * @param integer $id
     * @return JsonResponse
     * @throws NotFoundException
     */
    public function show(Request $request, $id)
    {
        $support = PublicSupport::find($id);
        if (!$support) {
            throw new NotFoundException('Support not found');
        }

        return response()->json([
            'data' => $support,
            'meta' => [],
        ]);
    }
}
