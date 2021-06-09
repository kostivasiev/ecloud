<?php
namespace App\Http\Middleware\NetworkRulePort;

use App\Models\V2\NetworkRule;
use App\Models\V2\NetworkRulePort;
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
        $networkRulePort = NetworkRulePort::forUser($request->user())->findOrFail($request->route('networkRulePortId'));

        if ($networkRulePort->networkRule->type == NetworkRule::TYPE_DHCP) {
            return response()->json([
                'errors' => [
                    [
                        'title' => 'Forbidden',
                        'detail' => 'The specified network rule port can not be deleted',
                        'status' => 403,
                    ]
                ]
            ], 403);
        }

        return $next($request);
    }
}
