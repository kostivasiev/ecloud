<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\FirewallRulePort\Create;
use App\Http\Requests\V2\FirewallRulePort\Update;
use App\Models\V2\FirewallRulePort;
use App\Resources\V2\FirewallRulePortResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use UKFast\DB\Ditto\QueryTransformer;

class FirewallRulePortController extends BaseController
{
    /**
     * @param Request $request
     * @param QueryTransformer $queryTransformer
     * @return AnonymousResourceCollection
     */
    public function index(Request $request, QueryTransformer $queryTransformer)
    {
        $collection = FirewallRulePort::forUser($request->user());
        $queryTransformer->config(FirewallRulePort::class)
            ->transform($collection);

        return FirewallRulePortResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    /**
     * @param Request $request
     * @param string $nicId
     * @return FirewallRulePortResource
     */
    public function show(Request $request, string $nicId)
    {
        return new FirewallRulePortResource(
            FirewallRulePort::forUser($request->user())->findOrFail($nicId)
        );
    }

    /**
     * @param Create $request
     * @return JsonResponse
     */
    public function store(Create $request)
    {
        $resource = new FirewallRulePort($request->only([
            'name',
            'firewall_rule_id',
            'protocol',
            'source',
            'destination'
        ]));
        $resource->save();
        return $this->responseIdMeta($request, $resource->getKey(), 201);
    }

    /**
     * @param Update $request
     * @param string $firewallRulePortId
     * @return JsonResponse
     */
    public function update(Update $request, string $firewallRulePortId)
    {
        $resource = FirewallRulePort::forUser(app('request')->user())->findOrFail($firewallRulePortId);
        $resource->fill($request->only([
            'name',
            'firewall_rule_id',
            'protocol',
            'source',
            'destination'
        ]));
        if ($request->has('protocol') && $request->get('protocol') === 'ICMPv4') {
            $resource->source = null;
            $resource->destination = null;
        }
        $resource->save();
        return $this->responseIdMeta($request, $resource->getKey(), 200);
    }

    /**
     * @param Request $request
     * @param string $firewallRulePortId
     * @return JsonResponse|\Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory
     */
    public function destroy(Request $request, string $firewallRulePortId)
    {
        $resource = FirewallRulePort::forUser($request->user())->findOrFail($firewallRulePortId);
        $resource->delete();
        return response(null, 204);
    }
}
