<?php

namespace App\Http\Controllers\V1;

use App\Events\V1\EncryptionEnabledOnSolutionEvent;
use App\Exceptions\V1\ServiceUnavailableException;
use App\Services\AccountsService;
use App\Solution\CanModifyResource;
use UKFast\DB\Ditto\QueryTransformer;

use UKFast\Api\Resource\Traits\ResponseHelper;
use UKFast\Api\Resource\Traits\RequestHelper;

use Illuminate\Http\Request;

use App\Models\V1\Solution;
use App\Exceptions\V1\SolutionNotFoundException;
use UKFast\Api\Exceptions\DatabaseException;

use App\Traits\V1\SanitiseRequestData;
use Illuminate\Support\Facades\Event;

class SolutionController extends BaseController
{
    use ResponseHelper, RequestHelper, SanitiseRequestData;

    /**
     * List all solutions
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $collectionQuery = static::getSolutionQuery($request);

        (new QueryTransformer($request))
            ->config(Solution::class)
            ->transform($collectionQuery);

        $solutions = $collectionQuery->paginate($this->perPage);

        return $this->respondCollection(
            $request,
            $solutions,
            200,
            null,
            [],
            ($this->isAdmin) ? null : Solution::VISIBLE_SCOPE_RESELLER
        );
    }

    /**
     * Show specific solution
     *
     * @param Request $request
     * @param $solutionId
     * @return \Illuminate\http\Response
     * @throws SolutionNotFoundException
     */
    public function show(Request $request, $solutionId)
    {
        return $this->respondItem(
            $request,
            static::getSolutionById($request, $solutionId),
            200,
            null,
            [],
            ($this->isAdmin) ? null : Solution::VISIBLE_SCOPE_RESELLER
        );
    }

    /**
     * Update Solution
     *
     * @param Request $request
     * @param $solutionId
     * @return \Illuminate\Http\Response
     * @throws DatabaseException
     * @throws SolutionNotFoundException
     * @throws \App\Solution\Exceptions\InvalidSolutionStateException
     * @throws \ReflectionException
     */
    public function update(Request $request, $solutionId)
    {
        $solution = static::getSolutionQuery($request)->find($solutionId);
        if (is_null($solution)) {
            throw new SolutionNotFoundException('Solution ID #' . $solutionId . ' not found');
        }

        (new CanModifyResource($solution))->validate();

        $rules = Solution::getRules();
        $rules = array_merge(
            $rules,
            [
                'environment' => ['nullable', 'in:Hybrid,Private'],
                'pod_id' => ['nullable', 'numeric'],
                'reseller_id' => ['nullable', 'numeric'],
                'status' => ['nullable']
            ]
        );

        if (!$this->isAdmin) {
            // Set whitelist of request params to pass to receiveItem()
            $this->sanitiseRequestData($request, ['name', 'encryption_default']);
        }

        $request['id'] = $solutionId;
        $this->validate($request, $rules);

        $encryptionStatus = $solution->ucs_reseller_encryption_enabled;

        $solution = $this->receiveItem($request, Solution::class);

        $solution->resource->save();

        if ($request->has('encryption_enabled')
            && $request->input('encryption_enabled') === true
            && ($encryptionStatus == 'No') // Only fire off the automation if the encryption was not already enabled.
        ) {
            Event::fire(new EncryptionEnabledOnSolutionEvent($solution->resource));
        }

        return $this->respondEmpty();
    }

    /**
     * get solution by ID
     * @param Request $request
     * @param $solutionId
     * @return mixed
     * @throws SolutionNotFoundException
     */
    public static function getSolutionById(Request $request, $solutionId)
    {
        $solution = static::getSolutionQuery($request)->find($solutionId);
        if (is_null($solution)) {
            throw new SolutionNotFoundException('Solution ID #' . $solutionId . ' not found', 'solution_id');
        }

        return $solution;
    }

    /**
     * create initial filtered query builder
     * @param Request $request
     * @return mixed
     */
    public static function getSolutionQuery(Request $request)
    {
        $solutionQuery = Solution::withReseller($request->user->resellerId);
        if (!$request->user->isAdministrator) {
            $solutionQuery->where('ucs_reseller_active', 'Yes');
            $solutionQuery->where('ucs_reseller_status', '!=', 'Cancelled');
            $solutionQuery->where('ucs_reseller_start_date', '<=', date('Y-m-d H:i:s'));
        }

        return $solutionQuery;
    }
}
