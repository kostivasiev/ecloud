<?php

namespace App\Http\Middleware;

use App\Exceptions\V2\MaxSshKeyPairException;
use App\Models\V2\SshKeyPair;
use Closure;
use Illuminate\Support\Facades\Auth;

class IsMaxSshKeyPairForCustomer
{
    /**
     * @param $request
     * @param Closure $next
     * @return mixed
     * @throws MaxSshKeyPairException
     */
    public function handle($request, Closure $next)
    {
        if (!$this->isWithinLimit()) {
            throw new MaxSshKeyPairException();
        }

        return $next($request);
    }

    public function isWithinLimit(): bool
    {
        $reseller_bypass = [
            7052, // UKFast - eCloud Testing
            22114, // UKFast - eCloud Automated Testing
        ];

        if (in_array(Auth::user()->resellerId(), $reseller_bypass)) {
            return true;
        }
        return (SshKeyPair::forUser(Auth::user())->get()->count() < config('defaults.ssh_key_pair.max_count'));
    }
}
