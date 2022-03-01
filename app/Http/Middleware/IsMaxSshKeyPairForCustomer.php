<?php

namespace App\Http\Middleware;

use App\Exceptions\V2\MaxSshKeyPairException;
use App\Models\V2\SshKeyPair;
use Closure;
use Illuminate\Support\Facades\Auth;

class IsMaxSshKeyPairForCustomer
{
    use ResellerBypass;

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
        if ($this->resellerBypass()) {
            return true;
        }
        return (SshKeyPair::forUser(Auth::user())->get()->count() < config('defaults.ssh_key_pair.max_count'));
    }
}
