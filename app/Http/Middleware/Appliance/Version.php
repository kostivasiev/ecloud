<?php

namespace App\Http\Middleware\Appliance;

use App\Models\V1\ApplianceVersion;
use Closure;
use Illuminate\Http\Response;

class Version
{
    const ERROR_CANT_FIND_APPLIANCE_VERSION = 'Can\'t find appliance version';

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param Closure $next
     * @param string|null $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        $version = ApplianceVersion::findOrFail($request->appliance_version_uuid);
        if ($version->active != 'Yes' ||
            $version->appliance->active != 'Yes' ||
            (!$request->user()->isAdmin() && $version->appliance->is_public != 'Yes')
        ) {
            abort(Response::HTTP_NOT_FOUND, self::ERROR_CANT_FIND_APPLIANCE_VERSION);
        }
        return $next($request);
    }
}
