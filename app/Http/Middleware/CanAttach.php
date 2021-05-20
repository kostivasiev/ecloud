<?php
namespace App\Http\Middleware;

use App\Exceptions\V2\DetachException;
use App\Exceptions\V2\MaxVolumeAttachmentException;
use App\Models\V2\Instance;
use Closure;
use Illuminate\Support\Facades\Auth;

class CanAttach
{
    /**
     * @param $request
     * @param Closure $next
     * @return mixed
     * @throws DetachException
     */
    public function handle($request, Closure $next)
    {
        $instance = Instance::forUser(Auth::user())->findOrFail($request->route('instanceId'));
        if ($instance->volumes()->get()->count() >= config('volume.instance.limit')) {
            throw new MaxVolumeAttachmentException();
        }

        return $next($request);
    }
}
