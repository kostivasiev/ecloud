<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\FirewallRule\Create;
use App\Http\Requests\V2\FirewallRule\Update;
use App\Models\V2\FirewallRule;
use App\Models\V2\FirewallRulePort;
use App\Resources\V2\FirewallRulePortResource;
use App\Resources\V2\FirewallRuleResource;
use App\Support\Sync;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Class FirewallRuleController
 * @package App\Http\Controllers\V2
 */
class FirewallRuleController extends BaseController
{
    /**
     * @param Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|\Illuminate\Support\HigherOrderTapProxy|mixed
     */
    public function index(Request $request)
    {
        $collection = FirewallRule::forUser($request->user());

        return FirewallRuleResource::collection(
            $collection->search()
                ->paginate(
                    $request->input('per_page', env('PAGINATION_LIMIT'))
                )
        );
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param string $firewallRuleId
     * @return FirewallRuleResource
     */
    public function show(Request $request, string $firewallRuleId)
    {
        return new FirewallRuleResource(
            FirewallRule::forUser($request->user())->findOrFail($firewallRuleId)
        );
    }

    public function ports(Request $request, string $firewallRuleId)
    {
        $collection = FirewallRule::forUser($request->user())
            ->findOrFail($firewallRuleId)->firewallRulePorts();

        return FirewallRulePortResource::collection(
            $collection->search()
                ->paginate(
                    $request->input('per_page', env('PAGINATION_LIMIT'))
                )
        );
    }

    /**
     * @param Create $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function store(Create $request)
    {
        $firewallRule = app()->make(FirewallRule::class);
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

        $task = $firewallRule->firewallPolicy->withTaskLock(function () use ($request, $firewallRule) {
            $firewallRule->save();

            if ($request->has('ports')) {
                foreach (array_unique($request->input('ports'), SORT_REGULAR) as $port) {
                    $port['firewall_rule_id'] = $firewallRule->id;
                    $firewallRulePort = new FirewallRulePort($port);
                    if ($firewallRulePort->protocol === 'ICMPv4') {
                        $firewallRulePort->source = null;
                        $firewallRulePort->destination = null;
                    }
                    $firewallRulePort->save();
                }
            }

            return $firewallRule->firewallPolicy->createSync(Sync::TYPE_UPDATE);
        });

        return $this->responseIdMeta($request, $firewallRule->id, 202, $task->id);
    }

    /**
     * @param Update $request
     * @param string $firewallRuleId
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Update $request, string $firewallRuleId)
    {
        $firewallRule = FirewallRule::foruser(Auth::user())->findOrFail($firewallRuleId);
        $firewallRule->fill($request->only([
            'name',
            'sequence',
            'deployed',
            'source',
            'destination',
            'action',
            'direction',
            'enabled'
        ]));

        $task = $firewallRule->firewallPolicy->withTaskLock(function () use ($request, $firewallRule) {
            $firewallRule->save();

            if ($request->filled('ports')) {
                $firewallRule->firewallRulePorts->each(function ($port) {
                    $port->delete();
                });
                foreach (array_unique($request->input('ports'), SORT_REGULAR) as $port) {
                    $port['firewall_rule_id'] = $firewallRule->id;
                    $firewallRulePort = new FirewallRulePort($port);
                    if ($firewallRulePort->protocol === 'ICMPv4') {
                        $firewallRulePort->source = null;
                        $firewallRulePort->destination = null;
                    }
                    $firewallRulePort->save();
                }
            }

            return $firewallRule->firewallPolicy->createSync(Sync::TYPE_UPDATE);
        });

        return $this->responseIdMeta($request, $firewallRule->id, 202, $task->id);
    }

    public function destroy(Request $request, string $firewallRuleId)
    {
        $firewallRule = FirewallRule::foruser($request->user())->findOrFail($firewallRuleId);

        $task = $firewallRule->firewallPolicy->withTaskLock(function () use ($firewallRule) {
            $firewallRule->firewallRulePorts->each(function ($port) {
                $port->delete();
            });

            $firewallRule->delete();

            return $firewallRule->firewallPolicy->createSync(Sync::TYPE_UPDATE);
        });

        return $this->responseTaskId($task->id);
    }
}
