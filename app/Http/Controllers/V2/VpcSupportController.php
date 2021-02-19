<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\VpcSupport\CreateRequest;
use App\Http\Requests\V2\VpcSupport\UpdateRequest;
use App\Models\V2\VpcSupport;
use App\Resources\V2\VpcSupportResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use UKFast\DB\Ditto\QueryTransformer;

/**
 * Class VpcSupportController
 * @package App\Http\Controllers\V2
 */
class VpcSupportController extends BaseController
{
    /**
     * @param Request $request
     * @param QueryTransformer $queryTransformer
     * @return Response
     */
    public function index(Request $request, QueryTransformer $queryTransformer)
    {
        $collection = VpcSupport::forUser($request->user());

        $queryTransformer->config(VpcSupport::class)
            ->transform($collection);

        return VpcSupportResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    /**
     * @param Request $request
     * @param string $vpcSupportId
     * @return VpcSupportResource
     */
    public function show(Request $request, string $vpcSupportId)
    {
        return new VpcSupportResource(
            VpcSupport::forUser($request->user())->findOrFail($vpcSupportId)
        );
    }

    /**
     * @param CreateRequest $request
     * @return JsonResponse
     */
    public function create(CreateRequest $request)
    {
        $vpcSupport = new VpcSupport($request->only([
            'vpc_id',
            'start_date',
            'end_date',
        ]));
        $vpcSupport->save();
        return $this->responseIdMeta($request, $vpcSupport->getKey(), 201);
    }

    /**
     * @param UpdateRequest $request
     * @param string $vpcSupportId
     * @return JsonResponse
     */
    public function update(UpdateRequest $request, string $vpcSupportId)
    {
        $vpcSupport = VpcSupport::forUser(Auth::user())->findOrFail($vpcSupportId);

        $vpcSupport->fill($request->only([
            'vpc_id',
            'start_date',
            'end_date',
        ]))->save();

        return $this->responseIdMeta($request, $vpcSupport->getKey(), 200);
    }

    /**
     * @param Request $request
     * @param string $vpcSupportId
     * @return Response|\Laravel\Lumen\Http\ResponseFactory
     */
    public function destroy(Request $request, string $vpcSupportId)
    {
        VpcSupport::forUser($request->user())->findOrFail($vpcSupportId)->delete();
        return response(null, 204);
    }
}
