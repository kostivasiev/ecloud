<?php

namespace App\Http\Controllers\V1;

use App\Exceptions\V1\ArtisanException;
use App\Models\V1\HostSet;
use App\Models\V1\Solution;
use App\Rules\V1\IsValidUuid;
use App\Services\Artisan\V1\ArtisanService;
use Illuminate\Http\Request;
use UKFast\Api\Exceptions\UnprocessableEntityException;

class HostSetController extends BaseController
{
    private static $model = HostSet::class;

    /**
     * Create a host set
     * When creating a host set we need to create it on all SAN's on the Pod for the reseller's solution
     * @param Request $request
     * @return \Illuminate\Http\Response
     * @throws ArtisanException
     * @throws \App\Exceptions\V1\SolutionNotFoundException
     * @throws \UKFast\Api\Resource\Exceptions\InvalidResourceException
     * @throws \UKFast\Api\Resource\Exceptions\InvalidResponseException
     * @throws \UKFast\Api\Resource\Exceptions\InvalidRouteException
     */
    public function create(Request $request)
    {
        $rules = HostSet::$rules;
        $this->validate($request, $rules);
        $solution = SolutionController::getSolutionById($request, $request->input('solution_id'));

        $identifier = $this->getNextHostSetIdentifier($solution);

        $hostSetName = '';
        $solution->pod->sans->each(function ($san) use ($solution, $identifier, &$hostSetName) {
            $artisan = app()->makeWith(ArtisanService::class, [['solution'=>$solution, 'san' => $san]]);

            $artisaResponse = $artisan->createHostSet($identifier);

            if (!$artisaResponse) {
                throw new ArtisanException('Failed to create host set: ' . $artisan->getLastError());
            }
            $hostSetName = $artisaResponse->name;
        });

        $hostSet = new HostSet;
        $hostSet->name = $hostSetName;
        $hostSet->ucs_reseller_id = $request->input('solution_id');
        $hostSet->save();

        return $this->respondSave($request, $hostSet, 201);
    }

    /**
     * Add a host to a host set
     * We need to add the host to the host set  on all SAN's on the Pod for the reseller's solution
     * @param Request $request
     * @param $hostSetId
     * @return \Illuminate\Http\Response
     * @throws UnprocessableEntityException
     * @throws \App\Exceptions\V1\HostNotFoundException
     */
    public function addHost(Request $request, $hostSetId)
    {
        $rules = [
            'host_set_id' => ['required', new IsValidUuid()],
            'host_id' => ['required', 'integer']
        ];
        $request['host_set_id'] = $hostSetId;
        $this->validate($request, $rules);
        $hostSet = HostSetController::getById($request, $hostSetId);
        $host = HostController::getHostById($request, $request->input('host_id'));

        if (empty($host->ucs_node_internal_name)) {
            throw new UnprocessableEntityException('Host is not mapped to a SAN entity');
        }

        $solution = $host->solution;
        $solution->pod->sans->each(function ($san) use ($solution, $hostSet, $host) {
            $artisan = app()->makeWith(ArtisanService::class, [['solution'=>$solution, 'san' => $san]]);

            $artisaResponse = $artisan->addHostToHostSet($hostSet->name, $host->ucs_node_internal_name);
            if (!$artisaResponse) {
                throw new ArtisanException('Failed to add host to host set: ' . $artisan->getLastError());
            }
        });

        return $this->respondEmpty();
    }


    /**
     * Host sets are of the format MCS_G0_SET_17106_(x),  where x is an increment for the solution.
     * Extracts the number and increments accordingly
     * @param Solution $solution
     * @return int|mixed
     */
    protected function getNextHostSetIdentifier(Solution $solution)
    {
        $index = 0;

        if ($solution->volumeSets->count() == 0) {
            return ++$index;
        }

        $solution->hostSets->each(function ($item) use (&$index, $solution) {
            if (preg_match('/\w+SET_'. $solution->getKey() . '_?(\d+)?/', $item->name, $matches) == true) {
                $numeric = $matches[1] ?? 1;
                $index = ($numeric > $index) ? (int) $numeric : $index;
            }
        });

        return ++$index;
    }

    /**
     * get item by ID
     * @param Request $request
     * @param $id
     * @return mixed
     */
    public static function getById(Request $request, $id)
    {
        return static::getQuery($request)->findorFail($id);
    }

    /**
     * Return a query builder for the model
     * @param Request $request
     * @return mixed
     */
    public static function getQuery(Request $request)
    {
        $query = self::$model::query();
        if ($request->user->resellerId != 0) {
            $query->join('ucs_reseller', (new self::$model)->getTable() . '.ucs_reseller_id', '=', 'ucs_reseller.ucs_reseller_id')
            ->where('ucs_reseller_reseller_id', '=', $request->user->resellerId);
        }

        return $query;
    }
}
