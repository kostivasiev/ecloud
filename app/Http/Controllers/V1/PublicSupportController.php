<?php

namespace App\Http\Controllers\V1;

use App\Models\V1\PublicSupport;
use App\Resources\V1\PublicSupportResource;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use UKFast\Api\Exceptions;
use UKFast\DB\Ditto\QueryTransformer;
use UKFast\Responses\UKFastResourceCollection;

class PublicSupportController extends BaseController
{
    /**
     * Display list of support resources
     *
     * @param Request $request
     * @param QueryTransformer $queryTransformer
     * @return UKFastResourceCollection
     */
    public function index(Request $request, QueryTransformer $queryTransformer)
    {
        $collection = PublicSupport::query();

        $queryTransformer->config(PublicSupport::class)
            ->transform($collection);

        return PublicSupportResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    /**
     * Display single support resource
     * @param Request $request
     * @param integer $id
     * @return PublicSupportResource
     * @throws Exceptions\NotFoundException
     */
    public function show(Request $request, $id)
    {
        $item = PublicSupport::find($id);
        if (!$item) {
            throw new Exceptions\NotFoundException('Support not found');
        }

        return new PublicSupportResource($item);
    }

    /**
     * Store new support resource
     * @param Request $request
     * @return PublicSupportResource
     * @throws Exceptions\UnauthorisedException
     */
    public function store(Request $request)
    {
        if (!$request->user()->isScoped()) {
            throw new Exceptions\UnauthorisedException('Unable to determine account id');
        }

        $item = app()->make(PublicSupport::class);
        $item->reseller_id = $request->user()->resellerId();
        $item->save();

        return new PublicSupportResource($item);
    }
}
