<?php

namespace App\Http\Controllers\V1;

use App\Events\V1\EncryptionEnabledOnSolutionEvent;
use App\Exceptions\V1\SolutionNotFoundException;
use App\Models\V1\DrsRule;
use App\Models\V1\Solution;
use App\Solution\CanModifyResource;
use App\Traits\V1\SanitiseRequestData;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use UKFast\Api\Exceptions\DatabaseException;
use UKFast\Api\Resource\Traits\RequestHelper;
use UKFast\Api\Resource\Traits\ResponseHelper;
use UKFast\DB\Ditto\QueryTransformer;

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
            Event::dispatch(new EncryptionEnabledOnSolutionEvent($solution->resource));
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
        $solutionQuery = Solution::withReseller($request->user()->resellerId());
        if (!$request->user()->isAdmin()) {
            $solutionQuery->where('ucs_reseller_active', 'Yes');
            $solutionQuery->where('ucs_reseller_status', '!=', 'Cancelled');
            $solutionQuery->where('ucs_reseller_start_date', '<=', date('Y-m-d H:i:s'));
        }

        return $solutionQuery;
    }

    /**
     * List Solution DRS Rules
     * @param Request $request
     * @param $solutionId
     * @return \Illuminate\Http\Response
     * @throws SolutionNotFoundException
     */
    public function getDrsRules(Request $request, $solutionId)
    {
        $solution = static::getSolutionById($request, $solutionId);

        $rules = $solution->drsRules();

        return $this->respondCollection(
            $request,
            $this->paginateDrsRuleData($rules)
        );
    }

    /**
     * Paginate template data
     * @param $rules
     * @return LengthAwarePaginator
     */
    protected function paginateDrsRuleData($rules)
    {
        if (!is_array($rules)) {
            $rules = [$rules];
        }

        $collection = new Collection($rules);

        $collection->transform(function (DrsRule $item) {
            return [
                'uuid' => $item->getUuid(),
                'name' => $item->getName(),
                'rule_type' => $item->getRuleType(),
                'enabled' => $item->getEnabled(),
                'host_group' => $item->getHostGroup(),
                'vm_group' => $item->getVmGroup(),
                'vms_in_rule' => $item->getVmsInRule()
            ];
        });

        $paginator = new LengthAwarePaginator(
            $collection->slice(
                LengthAwarePaginator::resolveCurrentPage('page') - 1 * $this->perPage,
                $this->perPage
            )->all(),
            count($collection),
            $this->perPage
        );

        return $paginator;
    }
}
