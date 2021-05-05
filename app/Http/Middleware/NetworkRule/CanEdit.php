<?php
namespace App\Http\Middleware\NetworkRule;

use App\Exceptions\V2\DetachException;
use App\Models\V2\NetworkRule;
use Closure;

class CanEdit
{
    /**
     * @param $request
     * @param Closure $next
     * @return mixed
     * @throws DetachException
     */
    public function handle($request, Closure $next)
    {
        $networkRule = NetworkRule::forUser($request->user())->findOrFail($request->route('networkRuleId'));

        if (in_array($networkRule->type, ['DHCP_Ingress', 'DHCP_Egress'])) {
            return response()->json([
                'errors' => [
                    [
                        'title' => 'Forbidden',
                        'detail' => 'The specified network rule is not editable',
                        'status' => 403,
                    ]
                ]
            ], 403);
        }

        return $next($request);
    }
}
