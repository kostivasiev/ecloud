<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\CreateMrrCommitmentRequest;
use App\Http\Requests\V2\Vpc\UpdateRequest;
use App\Models\V2\MrrCommitment;
use App\Resources\V2\MrrCommitmentResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use UKFast\DB\Ditto\QueryTransformer;

/**
 * Class MrrCommitmentController
 * @package App\Http\Controllers\V2
 */
class MrrCommitmentController extends BaseController
{
    /**
     * @param Request $request
     * @return Response
     */
    public function index(Request $request)
    {
        $collection = MrrCommitment::forUser($request->user);
        (new QueryTransformer($request))
            ->config(MrrCommitment::class)
            ->transform($collection);

        return MrrCommitmentResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    /**
     * @param Request $request
     * @param string $commitmentId
     * @return MrrCommitmentResource
     */
    public function show(Request $request, string $commitmentId)
    {
        return new MrrCommitmentResource(
            MrrCommitment::forUser($request->user)->findOrFail($commitmentId)
        );
    }

    /**
     * @param CreateMrrCommitmentRequest $request
     * @return JsonResponse
     */
    public function store(CreateMrrCommitmentRequest $request)
    {
        $commitment = new MrrCommitment($request->only([
            'contact_id',
            'employee_id',
            'name',
            'commitment_amount',
            'commitment_before_discount',
            'discount_rate',
            'term_length',
            'term_start_date',
            'term_end_date',
        ]));
        $commitment->reseller_id = $this->resellerId;
        $commitment->save();
        return $this->responseIdMeta($request, $commitment->getKey(), 201);
    }

    /**
     * @param UpdateRequest $request
     * @param string $commitmentId
     * @return JsonResponse
     */
    public function update(UpdateRequest $request, string $commitmentId)
    {
        $commitment = MrrCommitment::forUser(app('request')->user)->findOrFail($commitmentId);
        $commitment->update($request->only([
            'contact_id',
            'employee_id',
            'name',
            'commitment_amount',
            'commitment_before_discount',
            'discount_rate',
            'term_length',
            'term_start_date',
            'term_end_date',
        ]));

        if ($this->isAdmin) {
            $commitment->reseller_id = $request->input('reseller_id', $commitment->reseller_id);
        }
        $commitment->save();
        return $this->responseIdMeta($request, $commitment->getKey(), 200);
    }

    /**
     * @param string $commitmentId
     * @return JsonResponse
     * @throws \Exception
     */
    public function destroy(string $commitmentId)
    {
        $commitment = MrrCommitment::forUser(app('request')->user)->findOrFail($commitmentId);
        $commitment->delete();
        return response()->json([], 204);
    }
}
