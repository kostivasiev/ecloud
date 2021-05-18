<?php
namespace App\Http\Middleware\image;

use App\Models\V2\Image;
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
        $image = Image::forUser($request->user())->findOrFail($request->route('imageId'));

        // Only allow private images to be deleted by non-admins
        if ($request->user()->isScoped() && !$image->isOwner()) {
            return response()->json([
                'errors' => [
                    [
                        'title' => 'Forbidden',
                        'detail' => 'Only private images can be deleted',
                        'status' => 403,
                    ]
                ]
            ], 403);
        }

        return $next($request);
    }
}
