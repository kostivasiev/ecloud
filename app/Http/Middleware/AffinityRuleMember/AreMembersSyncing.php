<?php

namespace App\Http\Middleware\AffinityRuleMember;

use App\Models\V2\AffinityRule;
use App\Models\V2\AffinityRuleMember;
use App\Support\Sync;
use Closure;
use Illuminate\Support\Facades\Auth;

class AreMembersSyncing
{
    public function handle($request, Closure $next)
    {
        if ($request->route('affinityRuleMemberId')) {
            $affinityRuleMember = AffinityRuleMember::forUser(Auth::user())
                ->findOrFail($request->route('affinityRuleMemberId'));
            $affinityRule = $affinityRuleMember->affinityRule;
        } else {
            $affinityRule = AffinityRule::forUser(Auth::user())
                ->findOrFail($request->get('affinity_rule_id'));
        }

        $affinityRule->affinityRuleMembers
            ->each(function (AffinityRuleMember $affinityRuleMember) {
                if ($affinityRuleMember->sync->status == Sync::STATUS_INPROGRESS) {
                    $message = sprintf(
                        'Affinity rule %s currently has pending processes.',
                        $affinityRuleMember->affinityRule->id
                    );
                    return response()->json([
                        'title' => 'Forbidden',
                        'detail' => $message,
                        'status' => 403,
                    ], 403);
                }
            });

        return $next($request);
    }
}
