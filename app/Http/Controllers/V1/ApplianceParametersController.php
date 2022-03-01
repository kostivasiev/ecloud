<?php

namespace App\Http\Controllers\V1;

use App\Exceptions\V1\ParameterNotFoundException;
use App\Models\V1\ApplianceParameter;
use App\Rules\V1\IsValidUuid;
use Illuminate\Http\Request;
use UKFast\Api\Exceptions\BadRequestException;
use UKFast\Api\Exceptions\DatabaseException;
use UKFast\Api\Exceptions\ForbiddenException;
use UKFast\Api\Exceptions\UnprocessableEntityException;
use UKFast\Api\Resource\Traits\RequestHelper;
use UKFast\Api\Resource\Traits\ResponseHelper;
use UKFast\DB\Ditto\QueryTransformer;

class ApplianceParametersController extends BaseController
{
    use ResponseHelper, RequestHelper;

    /**
     * List all Appliances Versions collection
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     * @throws \Exception
     */
    public function index(Request $request)
    {
        $collectionQuery = static::getApplianceParametersQuery($request);

        (new QueryTransformer($request))
            ->config(ApplianceParameter::class)
            ->transform($collectionQuery);

        $applianceParameters = $collectionQuery->paginate($this->perPage);

        return $this->respondCollection(
            $request,
            $applianceParameters,
            200,
            null,
            [],
            ($this->isAdmin) ? null : ApplianceParameter::VISIBLE_SCOPE_RESELLER
        );
    }

    /**
     * Get a single ApplianceVersion resource
     *
     * @param Request $request
     * @param $parameterId
     * @return \Illuminate\Http\Response
     * @throws ParameterNotFoundException
     */
    public function show(Request $request, $parameterId)
    {
        $request['id'] = $parameterId;
        $this->validate($request, ['id' => [new IsValidUuid()]]);

        return $this->respondItem(
            $request,
            static::getApplianceParameterById($request, $parameterId),
            200,
            null,
            [],
            ($this->isAdmin) ? null : ApplianceParameter::VISIBLE_SCOPE_RESELLER
        );
    }


    /**
     * Create an appliance script parameter
     *
     * @param Request $request
     * - version_id - required UUID
     * - name - string, nullable
     * - type - enum, required
     * - required, optional, default true
     * - validation_rule, optional
     *
     * @return \Illuminate\Http\Response
     * @throws BadRequestException
     * @throws DatabaseException
     * @throws \App\Exceptions\V1\ApplianceVersionNotFoundException
     * @throws \UKFast\Api\Resource\Exceptions\InvalidResourceException
     * @throws \UKFast\Api\Resource\Exceptions\InvalidResponseException
     * @throws \UKFast\Api\Resource\Exceptions\InvalidRouteException
     * @throws UnprocessableEntityException
     */
    public function create(Request $request)
    {
        $rules = ApplianceParameter::getRules();
        $this->validate($request, $rules);

        //Validate the appliance exists
        ApplianceVersionController::getApplianceVersionById(
            $request,
            $request->input('version_id')
        );

        $applianceParameter = $this->receiveItem($request, ApplianceParameter::class);

        try {
            $applianceParameter->resource->save();
        } catch (\Illuminate\Database\QueryException $exception) {
            // 23000 Error code (Integrity Constraint Violation: parameter key already exists)
            if ($exception->getCode() == 23000) {
                throw new UnprocessableEntityException(
                    'A parameter with that key already exists for this appliance version'
                );
            }

            throw new DatabaseException('Unable to save Appliance parameter.');
        }

        return $this->respondSave(
            $request,
            $applianceParameter,
            201
        );
    }

    /**
     * Update an appliance script parameter
     * @param Request $request
     * @param $parameterId
     * @return \Illuminate\Http\Response
     * @throws BadRequestException
     * @throws DatabaseException
     * @throws ParameterNotFoundException
     * @throws \App\Exceptions\V1\ApplianceVersionNotFoundException
     */
    public function update(Request $request, $parameterId)
    {
        if (!$this->isAdmin) {
            throw new ForbiddenException();
        }

        $rules = ApplianceParameter::getUpdateRules();

        $request['id'] = $parameterId;
        $this->validate($request, $rules);

        //Do we want to change the Appliance Version the parameter is associated with?
        if ($request->has('version_id')) {
            $applianceParameter = ApplianceParametersController::getApplianceParameterById(
                $request,
                $parameterId
            );
            //Validate the appliance version exists
            $newApplianceVersion = ApplianceVersionController::getApplianceVersionById(
                $request,
                $request->input('version_id')
            );

            // Only allow a parameter to be moved between different versions of the same Application
            if ($newApplianceVersion->appliance->id != $applianceParameter->applianceVersion->appliance->id) {
                throw new BadRequestException('Parameters can not be moved between different Appliances.');
            }
        }

        // Update the resource
        $applianceParameter = $this->receiveItem($request, ApplianceParameter::class);

        try {
            $applianceParameter->resource->save();
        } catch (\Illuminate\Database\QueryException $exception) {
            throw new DatabaseException('Unable to update Appliance parameter.');
        }

        return $this->respondEmpty();
    }


    /**
     * Delete an appliance script parameter
     * @param Request $request
     * @param $parameterId
     * @return \Illuminate\Http\Response
     * @throws DatabaseException
     * @throws ParameterNotFoundException
     */
    public function delete(Request $request, $parameterId)
    {
        if (!$this->isAdmin) {
            throw new ForbiddenException();
        }
        $request['id'] = $parameterId;
        $this->validate($request, ['id' => [new IsValidUuid()]]);

        $parameter = static::getApplianceParameterById($request, $parameterId);

        try {
            $parameter->delete();
        } catch (\Exception $exception) {
            throw new DatabaseException('Failed to delete the parameter record');
        }

        return $this->respondEmpty();
    }

    /**
     * Load an appliance parameter (by UUID)
     * @param Request $request
     * @param $parameterId
     * @return mixed
     * @throws ParameterNotFoundException
     */
    public static function getApplianceParameterById(Request $request, $parameterId)
    {
        $applianceVersion = static::getApplianceParametersQuery($request)->find($parameterId);

        if (is_null($applianceVersion)) {
            throw new ParameterNotFoundException("Parameter with ID '$parameterId' was not found", 'id');
        }

        return $applianceVersion;
    }

    /**
     * Get appliance parameter query builder
     * @param $request
     * @return mixed
     */
    public static function getApplianceParametersQuery($request)
    {
        $applianceParametersQuery = ApplianceParameter::query()
            ->join(
                'appliance_version',
                'appliance_script_parameters_appliance_version_id',
                '=',
                'appliance_version_id'
            )
            ->join(
                'appliance',
                'appliance_id',
                '=',
                'appliance_version_appliance_id'
            );

        if ($request->user()->isScoped()) {
            $applianceParametersQuery->where('appliance_version_active', 'Yes');
            $applianceParametersQuery->where('appliance_active', 'Yes');
        }

        return $applianceParametersQuery;
    }
}
