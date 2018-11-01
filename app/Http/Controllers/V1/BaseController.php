<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use UKFast\Api\Resource\Traits\ResponseHelper;
use UKFast\Api\Resource\Traits\RequestHelper;
use UKFast\DB\Ditto\TransformsQueries;

class BaseController extends Controller
{
    use ResponseHelper;
    use RequestHelper;
    use TransformsQueries;

    // Number of items to return per page
    protected $count;

    protected $is_admin = false;

    protected $resellerId;

    /**
     * Controller constructor.
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        // Pagination limit. Try to set from Request, or default to .env PAGINATION_LIMIT
        $this->count = $request->input('per_page', env('PAGINATION_LIMIT'));

        if ($request->user->resellerId == 0) {
            $this->is_admin = true;
        }

        $this->resellerId = $request->user->resellerId;
    }
}
