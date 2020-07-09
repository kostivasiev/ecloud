<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use UKFast\Api\Resource\Traits\ResponseHelper;
use UKFast\Api\Resource\Traits\RequestHelper;

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
        $this->isAdmin = $request->user->isAdministrator;

        $this->resellerId = $request->user->resellerId;
    }

    /**
     * @param $request
     * @param $id
     * @param $statusCode
     * @return \Illuminate\Http\JsonResponse
     */
    public function responseIdMeta($request, $id, $statusCode)
    {
        return response()->json(
            [
                'data' => [
                    'id' => $id,
                ],
                'meta' => [
                    'location' => $request->fullUrl()
                ],
            ],
            $statusCode
        );
    }
}
