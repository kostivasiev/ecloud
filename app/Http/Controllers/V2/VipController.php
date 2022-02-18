<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\Vip\Create;
use App\Http\Requests\V2\Vip\Update;
use App\Models\V2\LoadBalancer;
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

        $queryTransformer->config(Vip::class)
            ->transform($collection);

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
        $lb = LoadBalancer::forUser(Auth::user())->findOrFail($request->input('load_balancer_id'));
        $lbNetwork = $lb->loadBalancerNetworks->firstOrFail();
        $vip = new Vip([
            'name' => $request->input('name'),
            'load_balancer_network_id' => $lbNetwork['id']
        ]);

        $task = $vip->syncSave([
            'allocate_floating_ip' => $request->input('allocate_floating_ip', false)
        ]);
        return $this->responseIdMeta($request, $vip->id, 202, $task->id);
    }

    /**
     * @param Update $request
     * @param string $vipId
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Update $request, string $vipId)
    {
        $vip = Vip::forUser(Auth::user())->findOrFail($vipId);
        $vip->fill($request->only([
            'name',
        ]));

        $task = $vip->syncSave();
        return $this->responseIdMeta($request, $vip->id, 202, $task->id);
    }

    public function destroy(Request $request, string $vipId)
    {
        $model = Vip::forUser($request->user())->findOrFail($vipId);
        $task = $model->syncDelete();
        return $this->responseTaskId($task->id);
    }
}
