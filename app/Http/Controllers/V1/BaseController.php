<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Auth;
use Illuminate\Http\Request;
use UKFast\Api\Resource\Traits\RequestHelper;
use UKFast\Api\Resource\Traits\ResponseHelper;
use UKFast\DB\Ditto\TransformsQueries;

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

        if (Auth::user()) {
            // is the client an admin
            $this->isAdmin = Auth::user()->isAdmin();

            $this->resellerId = Auth::user()->resellerId();
        }
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
