<?php

namespace App\Http\Middleware\Appliance;

use Closure;
use Illuminate\Http\Response;
use App\Models\V1\ApplianceVersion;

class Version
{
    const ERROR_CANT_FIND_APPLIANCE_VERSION = 'Can\'t find appliance version';

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        $version = ApplianceVersion::findOrFail($request->appliance_version_uuid);
        if ($request->appliance_version_uuid !== $version->appliance_version_uuid ||
            $version->active != 'Yes' ||
            $version->appliance->active != 'Yes' ||
            $version->appliance->is_public != 'Yes'
        ) {
            return response(self::ERROR_CANT_FIND_APPLIANCE_VERSION, Response::HTTP_NOT_FOUND);
        }

        return $next($request);
    }
}
