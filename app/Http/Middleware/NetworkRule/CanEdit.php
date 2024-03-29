<?php
namespace App\Http\Middleware\NetworkRule;

use App\Models\V2\NetworkRule;
use Closure;

class CanEdit
{
    /**
     * @param $request
     * @param Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $networkRule = NetworkRule::forUser($request->user())->findOrFail($request->route('networkRuleId'));

        if ($networkRule->type == NetworkRule::TYPE_DHCP) {
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
