<?php
namespace App\Http\Middleware\NetworkRule;

use App\Models\V2\NetworkRule;
use Closure;

class CanDelete
{
    /**
     * @param $request
     * @param Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $networkRule = NetworkRule::forUser($request->user())->findOrFail($request->route('networkRuleId'));

        if (in_array($networkRule->type, [
            NetworkRule::TYPE_DHCP_INGRESS,
            NetworkRule::TYPE_DHCP_EGRESS,
            NetworkRule::TYPE_CATCHALL
        ])) {
            return response()->json([
                'errors' => [
                    [
                        'title' => 'Forbidden',
                        'detail' => 'The specified network rule can not be deleted',
                        'status' => 403,
                    ]
                ]
            ], 403);
        }

        return $next($request);
    }
}
