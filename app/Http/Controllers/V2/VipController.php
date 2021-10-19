<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\Vip\Create;
use App\Http\Requests\V2\Vip\Update;
use App\Models\V2\Vip;
use App\Resources\V2\VipResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use UKFast\DB\Ditto\QueryTransformer;

/**
 * Class VipController
 * @package App\Http\Controllers\V2
 */
class VipController extends BaseController
{
    /**
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, QueryTransformer $queryTransformer)
    {
        $collection = Vip::forUser($request->user());

        return VipResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    /**
     * @param Request $request
     * @param string $vipId
     * @return VipResource
     */
    public function show(Request $request, string $vipId)
    {
        return new VipResource(
            Vip::forUser($request->user())->findOrFail($vipId)
        );
    }

    /**
     * @param Create $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(Create $request)
    {
        $vip = app()->make(Vip::class);
        $vip->fill($request->only([
            'ip_address_id'
        ]));
        $vip->syncSave([
            'network_id' => $request->input('network_id'),
            'allocate_floating_ip' => $request->input('allocate_floating_ip', 0)
        ]);
        return $this->responseIdMeta($request, $vip->id, 201);
    }

    /**
     * @param Update $request
     * @param string $vipId
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Update $request, string $vipId)
    {
        $model = Vip::forUser(Auth::user())->findOrFail($vipId);
        $model->fill($request->only([
            'ip_address_id'
        ]));

        $vip = $model->syncSave([
            'network_id' => $request->input('network_id'),
            'allocate_floating_ip' => $request->input('allocate_floating_ip', 0)
        ]);
        return $this->responseIdMeta($request, $vip->id, 202, $vip->id);
    }

    public function destroy(Request $request, string $vipId)
    {
        $model = Vip::forUser($request->user())->findOrFail($vipId);
        $task = $model->syncDelete();
        return $this->responseTaskId($task->id);
    }
}