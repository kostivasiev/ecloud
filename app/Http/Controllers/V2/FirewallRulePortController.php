<?php

namespace App\Http\Controllers\V2;

use App\Exceptions\V2\TaskException;
use App\Http\Requests\V2\FirewallRulePort\Create;
use App\Http\Requests\V2\FirewallRulePort\Update;
use App\Models\V2\FirewallRulePort;
use App\Resources\V2\FirewallRulePortResource;
use App\Support\Sync;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use UKFast\DB\Ditto\QueryTransformer;

class FirewallRulePortController extends BaseController
{
    public function index(Request $request, QueryTransformer $queryTransformer)
    {
        $collection = FirewallRulePort::forUser($request->user());
        $queryTransformer->config(FirewallRulePort::class)
            ->transform($collection);

        return FirewallRulePortResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
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

        $task = $firewallRulePort->firewallRule->firewallPolicy->withTaskLock(function () use ($firewallRulePort) {
            if (!$firewallRulePort->firewallRule->firewallPolicy->canCreateTask()) {
                throw new TaskException();
            }

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
            if (!$firewallRulePort->firewallRule->firewallPolicy->canCreateTask()) {
                throw new TaskException();
            }

            $firewallRulePort->save();
            return $firewallRulePort->firewallRule->firewallPolicy->createSync(Sync::TYPE_UPDATE);
        });

        return $this->responseTaskId($task->id);
    }

    public function destroy(Request $request, string $firewallRulePortId)
    {
        $firewallRulePort = FirewallRulePort::forUser($request->user())->findOrFail($firewallRulePortId);

        $task = $firewallRulePort->firewallRule->firewallPolicy->withTaskLock(function () use ($firewallRulePort) {
            if (!$firewallRulePort->firewallRule->firewallPolicy->canCreateTask()) {
                throw new TaskException();
            }

            $firewallRulePort->delete();
            return $firewallRulePort->firewallRule->firewallPolicy->createSync(Sync::TYPE_UPDATE);
        });

        return $this->responseTaskId($task->id);
    }
}
