<?php

namespace App\Http\Controllers\V1;

use App\Exceptions\V1\ParameterNotFoundException;
use App\Models\V1\ApplianceParameters;
use App\Rules\V1\IsValidUuid;
use UKFast\DB\Ditto\QueryTransformer;

use UKFast\Api\Resource\Traits\ResponseHelper;
use UKFast\Api\Resource\Traits\RequestHelper;

use Illuminate\Http\Request;

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
            ->config(ApplianceParameters::class)
            ->transform($collectionQuery);

        $applianceVersions = $collectionQuery->paginate($this->perPage);

        return $this->respondCollection(
            $request,
            $applianceVersions
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
            static::getApplianceParameterById($request, $parameterId)
        );
    }


    /**
     * Load an appliance parameter (by UUID)
     * @param Request $request
     * @param $applianceParameterId
     * @return mixed
     * @throws ParameterNotFoundException
     */
    public static function getApplianceParameterById(Request $request, $applianceParameterId)
    {
        $applianceVersion = static::getApplianceParametersQuery($request)->find($applianceParameterId);

        if (is_null($applianceVersion)) {
            throw new ParameterNotFoundException("Parameter with ID '$applianceParameterId' was not found", 'id');
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
        $applianceParametersQuery = ApplianceParameters::query()
            ->join(
                'appliance_version',
                'appliance_script_parameters_appliance_version_id',
                'appliance_version_id'
            )
            ->join(
                'appliance',
                'appliance_id',
                'appliance_version_appliance_id'
            )
            ->whereNull('appliance_version_deleted_at');

        if ($request->user->resellerId != 0) {
            $applianceParametersQuery->where('appliance_version_active', 'Yes');
            $applianceParametersQuery->where('appliance_active', 'Yes');
        }

        return $applianceParametersQuery;
    }
}
