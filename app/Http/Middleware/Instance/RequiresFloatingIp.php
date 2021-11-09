<?php

namespace App\Http\Middleware\Instance;

use App\Models\V2\Image;
use Closure;
use Illuminate\Support\Facades\Auth;

class RequiresFloatingIp
{
    public function handle($request, Closure $next)
    {
        $image = Image::forUser(Auth::user())->findOrFail($request->input('image_id'));

        if ($image->imageMetadata->pluck('key', 'value')->flip()->get('ukfast.fip.required') == 'true' &&
            empty($request->input('floating_ip_id')) &&
            empty($request->input('requires_floating_ip', false)))
        {
            return response()->json([
                'errors' => [
                    [
                        'title' => 'Validation Error',
                        'detail' => 'The specified instance image requires a floating IP',
                        'status' => 422,
                    ]
                ]
            ], 422);
        }

        return $next($request);
    }
}
