<?php
namespace App\Http\Middleware\image;

use App\Models\V2\Image;
use Closure;

class CanUpdate
{
    /**
     * @param $request
     * @param Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $image = Image::forUser($request->user())->findOrFail($request->route('imageId'));

        // Only allow non-admins to update private images
        if ($request->user()->isScoped() && $image->visibility != Image::VISIBILITY_PRIVATE) {
            return response()->json([
                'errors' => [
                    [
                        'title' => 'Forbidden',
                        'detail' => 'Only private images can be updated',
                        'status' => 403,
                    ]
                ]
            ], 403);
        }

        return $next($request);
    }
}
