<?php
namespace App\Http\Middleware;

use App\Exceptions\V2\DetachException;
use App\Models\V2\Volume;
use Closure;

/**
 * @deprecated use instance volume
 */
class CanDetach
{
    /**
     * @param $request
     * @param Closure $next
     * @return mixed
     * @throws DetachException
     */
    public function handle($request, Closure $next)
    {
        $volume = Volume::forUser($request->user())->findOrFail($request->route('volumeId'));

        if ($volume->os_volume) {
            throw new DetachException();
        }

        return $next($request);
    }
}
