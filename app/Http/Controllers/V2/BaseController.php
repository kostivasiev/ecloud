<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use UKFast\Api\Resource\Traits\RequestHelper;
use UKFast\Api\Resource\Traits\ResponseHelper;

class BaseController extends Controller
{
    use ResponseHelper;
    use RequestHelper;

    // Number of items to return per page
    protected $perPage;

    // API clients admin status
    protected $isAdmin = false;

    // Customers Reseller ID
    protected $resellerId;

    /**
     * Controller constructor.
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        // Pagination limit. Try to set from Request, or default to .env PAGINATION_LIMIT
        $this->perPage = $request->input('per_page', env('PAGINATION_LIMIT'));

        // is the client an admin
        $this->isAdmin = Auth::user()->isAdmin();

        $this->resellerId = Auth::user()->resellerId();
    }

    /**
     * @param $request
     * @param $id
     * @param $statusCode
     * @return \Illuminate\Http\JsonResponse
     */
    public function responseIdMeta($request, $id, $statusCode, $taskId = null)
    {
        $location = $request->fullUrlIs("*/$id") ? URL::current() : sprintf('%s/%s', URL::current(), $id);

        $data = ['id' => $id];

        if ($taskId) {
            $data['task_id'] = $taskId;
        }

        return response()->json(
            [
                'data' => $data,
                'meta' => [
                    'location' => $location,
                ],
            ],
            $statusCode
        );
    }

    public function responseTaskId($taskId, $statusCode = 202)
    {
        return response()->json(
            [
                'data' => [
                    'task_id' => $taskId
                ],
                'meta' => [
                    'location' => config('app.url') . 'v2/tasks/' . $taskId,
                ],
            ],
            $statusCode
        );
    }
}
