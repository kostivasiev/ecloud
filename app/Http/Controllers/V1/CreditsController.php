<?php

namespace App\Http\Controllers\V1;

use App\Exceptions\V1\ServiceUnavailableException;
use App\Services\AccountsService;

use UKFast\Api\Resource\Traits\ResponseHelper;
use UKFast\Api\Resource\Traits\RequestHelper;

use Illuminate\Http\Request;

class CreditsController extends BaseController
{
    use ResponseHelper, RequestHelper;

    /**
     * Display the reseller's eCloud credits
     *
     * @middleware HasResellerId
     *
     * @param Request $request
     * @param AccountsService $accountsService
     * @return \Illuminate\Http\Response
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function index(Request $request, AccountsService $accountsService)
    {
        //Only show eCloud related encryption credits
        $credits = $accountsService->scopeResellerId($this->resellerId)->getVmEncryptionCredits();

        if (!$credits) {
            $credits = [];
        }

        $credits = collect([$credits]);

        return $this->respondCollection(
            $request,
            $credits
        );
    }
}
