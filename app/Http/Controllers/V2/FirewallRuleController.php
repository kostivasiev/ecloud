<?php

namespace App\Http\Controllers\V2;

use App\Events\V2\FirewallRule\Saved;
use App\Http\Requests\V2\FirewallRule\Create;
use App\Http\Requests\V2\FirewallRule\Update;
use App\Models\V2\FirewallRule;
use App\Models\V2\FirewallRulePort;
use App\Resources\V2\FirewallRulePortResource;
use App\Resources\V2\FirewallRuleResource;
use Illuminate\Http\Request;
use UKFast\DB\Ditto\QueryTransformer;

/**
 * Class FirewallRuleController
 * @package App\Http\Controllers\V2
 */
class FirewallRuleController extends BaseController
{
    /**
     * @param Request $request
     * @param QueryTransformer $queryTransformer
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|\Illuminate\Support\HigherOrderTapProxy|mixed
     */
    public function index(Request $request, QueryTransformer $queryTransformer)
    {
        $collection = FirewallRule::forUser($request->user);

        $queryTransformer->config(FirewallRule::class)
            ->transform($collection);

        return FirewallRuleResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param string $firewallRuleId
     * @return FirewallRuleResource
     */
    public function show(Request $request, string $firewallRuleId)
    {
        return new FirewallRuleResource(
            FirewallRule::forUser($request->user)->findOrFail($firewallRuleId)
        );
    }

    public function ports(Request $request, QueryTransformer $queryTransformer, string $firewallRuleId)
    {
        $collection = FirewallRule::forUser($request->user)->findOrFail($firewallRuleId)->firewallRulePorts();
        $queryTransformer->config(FirewallRulePort::class)
            ->transform($collection);

        return FirewallRulePortResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    /**
     * @param Create $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function store(Create $request)
    {
        $only = [
            'name',
            'sequence',
            'deployed',
            'firewall_policy_id',
            'source',
            'destination',
            'action',
            'direction',
            'enabled'
        ];

        if ($request->has('ports')) {
            $firewallRule = FirewallRule::withoutEvents(function () use ($request, $only) {
                $firewallRule = new FirewallRule();
                $firewallRule->fill($request->only($only));
                $firewallRule::addCustomKey($firewallRule);
                $firewallRule->name = $firewallRule->name ?? $firewallRule->id;
                $firewallRule->save();

                foreach ($request->input('ports') as $port) {
                    FirewallRulePort::withoutEvents(function () use ($firewallRule, $port) {
                        $firewallRulePort = new FirewallRulePort($port);
                        $firewallRulePort::addCustomKey($firewallRulePort);
                        $firewallRulePort->name = $firewallRulePort->id;
                        $firewallRulePort->firewall_rule_id = $firewallRule->getKey();
                        $firewallRulePort->save();
                    });
                }

                return $firewallRule;
            });
            event(new Saved($firewallRule));
            return $this->responseIdMeta($request, $firewallRule->getKey(), 201);
        }

        $firewallRule = new FirewallRule();
        $firewallRule->fill($request->only($only));
        $firewallRule->save();

        return $this->responseIdMeta($request, $firewallRule->getKey(), 201);
    }

    /**
     * @param Update $request
     * @param string $firewallRuleId
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Update $request, string $firewallRuleId)
    {
        $firewallRule = FirewallRule::foruser(app('request')->user)->findOrFail($firewallRuleId);
        $firewallRule->fill($request->only([
            'name',
            'sequence',
            'deployed',
            'firewall_policy_id',
            'source',
            'destination',
            'action',
            'direction',
            'enabled'
        ]));
        $firewallRule->save();

        if ($request->has('ports')) {
            $firewallRule->firewallRulePorts->delete();
            foreach ($request->input('ports') as $port) {
                FirewallRulePort::withoutEvents(function () use ($port, $firewallRule) {
                    $port['firewall_rule_id'] = $firewallRule->getKey();
                    $firewallRulePort = new FirewallRulePort($port);
                    $firewallRulePort->save();
                });
            }
        }

        return $this->responseIdMeta($request, $firewallRule->getKey(), 200);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param string $firewallRuleId
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, string $firewallRuleId)
    {
        $item = FirewallRule::foruser(app('request')->user)->findOrFail($firewallRuleId);
        $item->delete();
        return response()->json([], 204);
    }
}
