<?php
namespace App\Http\Middleware\Volume;

use App\Models\V2\Image;
use App\Models\V2\Volume;
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
        $volume = Volume::forUser($request->user())->findOrFail($request->route('volumeId'));
        if (!empty($volume->volume_group_id)) {
            return response()->json([
                'errors' => [
                    [
                        'title' => 'Forbidden',
                        'detail' => 'Volumes that are members of a volume group cannot be deleted',
                        'status' => 403,
                    ]
                ]
            ], 403);
        }

        return $next($request);
    }
}
