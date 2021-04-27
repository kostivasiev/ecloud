<?php

namespace App\Http\Controllers\V2;

use App\Exceptions\SyncException;
use App\Http\Requests\V2\NetworkRule\Create;
use App\Http\Requests\V2\NetworkRule\Update;
use App\Models\V2\NetworkRule;
use App\Resources\V2\NetworkRuleResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use UKFast\DB\Ditto\QueryTransformer;

class NetworkRuleController extends BaseController
{
    public function index(Request $request, QueryTransformer $queryTransformer)
    {
        $collection = NetworkRule::forUser($request->user());

        $queryTransformer->config(NetworkRule::class)
            ->transform($collection);

        return NetworkRuleResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    public function show(Request $request, string $networkRuleId)
    {
        return new NetworkRuleResource(NetworkRule::forUser($request->user())->findOrFail($networkRuleId));
    }

    public function store(Create $request)
    {
        $networkRule = app()->make(NetworkRule::class);
        $networkRule->fill($request->only([
            'network_policy_id',
            'name',
            'sequence',
            'source',
            'destination',
            'action',
            'enabled',
        ]));

        $networkRule->networkPolicy->withSyncLock(function () use ($request, $networkRule) {
            if (!$networkRule->networkPolicy->canSync()) {
                throw new SyncException();
            }

            $networkRule->save();

            $networkRule->networkPolicy->save();
        });

        return $this->responseIdMeta($request, $networkRule->id, 202);
    }

    public function update(Update $request, string $networkRuleId)
    {
        $networkRule = NetworkRule::forUser(Auth::user())->findOrFail($networkRuleId);
        $networkRule->fill($request->only([
            'name',
            'sequence',
            'source',
            'destination',
            'action',
            'enabled',
        ]));

        $networkRule->networkPolicy->withSyncLock(function () use ($request, $networkRule) {
            if (!$networkRule->networkPolicy->canSync()) {
                throw new SyncException();
            }

            $networkRule->save();

            $networkRule->networkPolicy->save();
        });

        return $this->responseIdMeta($request, $networkRule->id, 202);
    }

    public function destroy(Request $request, string $networkRuleId)
    {
        $networkRule = NetworkRule::forUser($request->user())->findOrFail($networkRuleId);
            
        $networkRule->networkPolicy->withSyncLock(function () use ($networkRule) {
            if (!$networkRule->networkPolicy->canSync()) {
                throw new SyncException();
            }

            $networkRule->networkRulePorts->each(function ($port) {
                $port->delete();
            });

            $networkRule->delete();

            $networkRule->networkPolicy->save();
        });


        return response('', 202);
    }
}
