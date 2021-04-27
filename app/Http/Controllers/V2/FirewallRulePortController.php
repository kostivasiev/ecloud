<?php

namespace App\Http\Controllers\V2;

use App\Exceptions\SyncException;
use App\Http\Requests\V2\FirewallRulePort\Create;
use App\Http\Requests\V2\FirewallRulePort\Update;
use App\Models\V2\FirewallRulePort;
use App\Resources\V2\FirewallRulePortResource;
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
        $resource = new FirewallRulePort($request->only([
            'name',
            'firewall_rule_id',
            'protocol',
            'source',
            'destination'
        ]));

        $resource->firewallRule->firewallPolicy->withSyncLock(function () use ($resource) {
            if (!$resource->firewallRule->firewallPolicy->canSync()) {
                throw new SyncException();
            }

            $resource->save();
            $resource->firewallRule->firewallPolicy->save();
        });

        return $this->responseIdMeta($request, $resource->getKey(), 202);
    }

    public function update(Update $request, string $firewallRulePortId)
    {
        $resource = FirewallRulePort::forUser(Auth::user())->findOrFail($firewallRulePortId);
        $resource->fill($request->only([
            'name',
            'protocol',
            'source',
            'destination'
        ]));
        if ($request->has('protocol') && $request->get('protocol') === 'ICMPv4') {
            $resource->source = null;
            $resource->destination = null;
        }

        $resource->firewallRule->firewallPolicy->withSyncLock(function () use ($resource) {
            if (!$resource->firewallRule->firewallPolicy->canSync()) {
                throw new SyncException();
            }

            $resource->save();
            $resource->firewallRule->firewallPolicy->save();
        });

        return $this->responseIdMeta($request, $resource->getKey(), 202);
    }

    public function destroy(Request $request, string $firewallRulePortId)
    {
        $resource = FirewallRulePort::forUser($request->user())->findOrFail($firewallRulePortId);

        $resource->firewallRule->firewallPolicy->withSyncLock(function () use ($resource) {
            if (!$resource->firewallRule->firewallPolicy->canSync()) {
                throw new SyncException();
            }

            $resource->delete();
            $resource->firewallRule->firewallPolicy->save();
        });

        return response('', 202);
    }
}
