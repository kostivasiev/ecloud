<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\FirewallRulePort\Create;
use App\Http\Requests\V2\FirewallRulePort\Update;
use App\Models\V2\FirewallRulePort;
use App\Resources\V2\FirewallRulePortResource;
use App\Support\Sync;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FirewallRulePortController extends BaseController
{
    public function index(Request $request)
    {
        $collection = FirewallRulePort::forUser($request->user());

        return FirewallRulePortResource::collection(
            $collection->search()
                ->paginate(
                    $request->input('per_page', env('PAGINATION_LIMIT'))
                )
        );
    }

    public function show(Request $request, string $firewallRulePortId)
    {
        return new FirewallRulePortResource(
            FirewallRulePort::forUser($request->user())->findOrFail($firewallRulePortId)
        );
    }

    public function store(Create $request)
    {
        $firewallRulePort = new FirewallRulePort($request->only([
            'name',
            'firewall_rule_id',
            'protocol',
            'source',
            'destination'
        ]));

        if ($request->has('protocol') && $request->get('protocol') === 'ICMPv4') {
            $firewallRulePort->source = null;
            $firewallRulePort->destination = null;
        }

        $task = $firewallRulePort->firewallRule->firewallPolicy->withTaskLock(function () use ($firewallRulePort) {
            $firewallRulePort->save();
            return $firewallRulePort->firewallRule->firewallPolicy->createSync(Sync::TYPE_UPDATE);
        });

        return $this->responseIdMeta($request, $firewallRulePort->id, 202, $task->id);
    }

    public function update(Update $request, string $firewallRulePortId)
    {
        $firewallRulePort = FirewallRulePort::forUser(Auth::user())->findOrFail($firewallRulePortId);
        $firewallRulePort->fill($request->only([
            'name',
            'protocol',
            'source',
            'destination'
        ]));
        if ($request->has('protocol') && $request->get('protocol') === 'ICMPv4') {
            $firewallRulePort->source = null;
            $firewallRulePort->destination = null;
        }

        $task = $firewallRulePort->firewallRule->firewallPolicy->withTaskLock(function () use ($firewallRulePort) {
            $firewallRulePort->save();
            return $firewallRulePort->firewallRule->firewallPolicy->createSync(Sync::TYPE_UPDATE);
        });

        return $this->responseIdMeta($request, $firewallRulePort->id, 202, $task->id);
    }

    public function destroy(Request $request, string $firewallRulePortId)
    {
        $firewallRulePort = FirewallRulePort::forUser($request->user())->findOrFail($firewallRulePortId);

        $task = $firewallRulePort->firewallRule->firewallPolicy->withTaskLock(function () use ($firewallRulePort) {
            $firewallRulePort->delete();
            return $firewallRulePort->firewallRule->firewallPolicy->createSync(Sync::TYPE_UPDATE);
        });

        return $this->responseTaskId($task->id);
    }
}
