<?php

namespace App\Http\Controllers\V1;

use App\Services\AccountsService;
use Illuminate\Http\Request;
use App\Services\V1\Resource\Traits\RequestHelper;
use App\Services\V1\Resource\Traits\ResponseHelper;

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
