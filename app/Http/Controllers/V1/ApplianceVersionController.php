<?php

namespace App\Http\Controllers\V1;

use App\Exceptions\V1\ApplianceActiveException;
use App\Exceptions\V1\ApplianceNotFoundException;
use App\Exceptions\V1\ApplianceVersionNotFoundException;
use App\Exceptions\V1\InvalidJsonException;
use App\Models\V1\ApplianceParameter;
use App\Models\V1\ApplianceVersion;
use App\Rules\V1\IsValidUuid;
use Illuminate\Http\Request;
use Mustache_Engine;
use Mustache_Tokenizer;
use UKFast\Api\Exceptions\BadRequestException;
use UKFast\Api\Exceptions\DatabaseException;
use UKFast\Api\Exceptions\ForbiddenException;
use UKFast\Api\Exceptions\UnprocessableEntityException;
use App\Services\V1\Resource\Traits\RequestHelper;
use App\Services\V1\Resource\Traits\ResponseHelper;
use UKFast\DB\Ditto\QueryTransformer;

class ApplianceVersionController extends BaseController
{
    use ResponseHelper, RequestHelper;

    /**
     * List all Appliance Versions collection
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     * @throws \Exception
     */
    public function index(Request $request)
    {
        if (!$this->isAdmin) {
            throw new ForbiddenException();
        }
        $collectionQuery = static::getApplianceVersionQuery($request);

        (new QueryTransformer($request))
            ->config(ApplianceVersion::class)
            ->transform($collectionQuery);

        $applianceVersions = $collectionQuery->paginate($this->perPage);

        return $this->respondCollection(
            $request,
            $applianceVersions,
            200,
            null,
            [],
            ($this->isAdmin) ? null : ApplianceVersion::VISIBLE_SCOPE_RESELLER
        );
    }

    /**
     * Get a single ApplianceVersion resource
     *
     * @param Request $request
     * @param $applianceVersionId
     * @return \Illuminate\Http\Response
     * @throws ApplianceVersionNotFoundException
     * @throws ForbiddenException
     */
    public function show(Request $request, $applianceVersionId)
    {
        if (!$this->isAdmin) {
            throw new ForbiddenException();
        }
        $request['id'] = $applianceVersionId;
        $this->validate($request, ['id' => [new IsValidUuid()]]);

        return $this->respondItem(
            $request,
            static::getApplianceVersionById($request, $applianceVersionId),
            200,
            null,
            [],
            ($this->isAdmin) ? null : ApplianceVersion::VISIBLE_SCOPE_RESELLER
        );
    }

    /**
     * Parse the variables from a script template.
     *
     * Validate the script template, scan and tokenize template source.
     * Throws Mustache_Exception_SyntaxException when mismatched section tags are encountered
     *
     * @param $script
     * @return array
     * @throws BadRequestException
     */
    public static function getScriptVariables($script)
    {
        $Mustache_Engine = new Mustache_Engine;

        $Mustache_Tokenizer = $Mustache_Engine->getTokenizer();

        try {
            $tokens = $Mustache_Tokenizer->scan($script);
        } catch (\Mustache_Exception_SyntaxException $exception) {
            throw new BadRequestException('Invalid script template.');
        }

        // Extract script variable tokens
        $variableTokens = array_filter(
            $tokens,
            function ($var) {
                return in_array(
                    $var['type'],
                    [
                        Mustache_Tokenizer::T_ESCAPED,
                        Mustache_Tokenizer::T_UNESCAPED,
                        Mustache_Tokenizer::T_UNESCAPED_2
                    ]
                );
            }
        );

        $scriptVariables = array_unique(array_column($variableTokens, 'name'));
        return $scriptVariables;
    }

    /**
     * Create an appliance version.
     *
     * Stores an appliance version record and also stores any associated
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     * @throws ApplianceNotFoundException
     * @throws BadRequestException
     * @throws DatabaseException
     * @throws ForbiddenException
     * @throws InvalidJsonException
     * @throws UnprocessableEntityException
     * @throws \App\Services\V1\Resource\Exceptions\InvalidResourceException
     * @throws \App\Services\V1\Resource\Exceptions\InvalidResponseException
     * @throws \App\Services\V1\Resource\Exceptions\InvalidRouteException
     */
    public function create(Request $request)
    {
        if (!$this->isAdmin) {
            throw new ForbiddenException();
        }

        // Validates request has correct JSON format
        if (empty($request->json()->all()) || empty($request->request->all())) {
            throw new InvalidJsonException("Invalid JSON. " . json_last_error_msg());
        }

        // Validate the appliance version
        $rules = ApplianceVersion::getRules();
        $this->validate($request, $rules);

        $scriptVariables = self::getScriptVariables($request->input('script_template', ''));

        //Validate the appliance exists
        ApplianceController::getApplianceById($request, $request->input('appliance_id'));

        //Receive the user data
        $applianceVersion = $this->receiveItem($request, ApplianceVersion::class);

        // Validate appliance version parameters
        if ($request->has('parameters')) {
            $rules = ApplianceParameter::getRules();
            $this->validate(
                $request,
                [
                    'parameters' => ['array'],
                    'parameters.*.name' => $rules['name'],
                    'parameters.*.type' => $rules['type'],
                    'parameters.*.key' => $rules['key'],
                    'parameters.*.description' => $rules['description'],
                    'parameters.*.required' => $rules['required'],
                    'parameters.*.validation_rule' => $rules['validation_rule'],
                ]
            );
        }

        $database = app('db')->connection('ecloud');
        $database->beginTransaction();

        // Save the appliance version record
        $errorMessage = 'Failed to save appliance version.';
        try {
            $applianceVersion->save();
        } catch (\Illuminate\Database\QueryException $exception) {
            // 23000 Error code (Integrity Constraint Violation: version already exists for this application)
            if ($exception->getCode() == 23000) {
                $errorMessage .= ' Version \'' . $request->input('version');
                $errorMessage .= '\' already exists for this appliance.';
                throw new UnprocessableEntityException($errorMessage);
            }

            throw new DatabaseException($errorMessage);
        }

        // Reload the model to populate the auto-generated data from the database
        $applianceVersion = $applianceVersion->resource->refresh();

        /**
         * Loop through and save any version parameters, we've already validated the data but if any errors occur
         * whilst saving, roll back the entire transaction to remove ALL version parameters AND THE VERSION RECORD.
         */
        foreach ($request->input('parameters', []) as $parameter) {
            $applianceParameter = new ApplianceParameter();
            $applianceParameter->appliance_version_id = $applianceVersion->id;
            $applianceParameter->name = $parameter['name'];
            $applianceParameter->type = $parameter['type'];
            $applianceParameter->key = $parameter['key'];

            if (isset($parameter['description'])) {
                $applianceParameter->description = $parameter['description'];
            }

            // Default parameter to required unless stated otherwise
            $isRequired = true;
            if (isset($parameter['required']) && $parameter['required'] === false) {
                $isRequired = false;
            }

            $applianceParameter->required = ($isRequired) ? 'Yes' : 'No';

            // If the parameter is required and is not found in the script template, error.
            if ($isRequired && !in_array($parameter['key'], $scriptVariables)) {
                $database->rollBack();
                throw new BadRequestException(
                    'Required parameter \'' . $parameter['name'] . '\' with key \''
                    . $parameter['key'] . '\' was not found in script template'
                );
            }

            if (isset($parameter['validation_rule'])) {
                $applianceParameter->validation_rule = $parameter['validation_rule'];
            }

            if (!$applianceParameter->save()) {
                $database->rollback();
                throw new DatabaseException(
                    'Failed to save Appliance version. Invalid parameter \'' . $parameter['name'] . '\''
                );
            }
        }

        $database->commit();

        return $this->respondSave(
            $request,
            $applianceVersion,
            201
        );
    }


    /**
     * Update an appliance version
     *
     * @param Request $request
     * - appliance_id - uuid, optional
     * - version - string, optional
     * - script_template - string, optional
     * - active - boolean, optional
     * @param $applianceVersionId
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     * @throws ApplianceNotFoundException
     * @throws ApplianceVersionNotFoundException
     * @throws DatabaseException
     * @throws ForbiddenException
     * @throws InvalidJsonException
     * @throws UnprocessableEntityException
     * @throws BadRequestException
     */
    public function update(Request $request, $applianceVersionId)
    {
        if (!$this->isAdmin) {
            throw new ForbiddenException();
        }

        if (empty($request->json()->all()) || empty($request->request->all())) {
            throw new InvalidJsonException("Invalid JSON. " . json_last_error_msg());
        }

        // Validate the appliance version exists
        $applianceVersion = static::getApplianceVersionById($request, $applianceVersionId);

        $rules = ApplianceVersion::getUpdateRules();
        $request['id'] = $applianceVersionId;
        $this->validate($request, $rules);

        if ($request->has('script_template')) {
            $scriptVariables = self::getScriptVariables($request->input('script_template'));

            // Load the required parameters and check that we have placeholders for them all in the script
            $requiredParameters = $applianceVersion->getParameterList(true);

            $missingRequiredParameters = array_diff($requiredParameters, $scriptVariables);

            if (!empty($missingRequiredParameters)) {
                $error_message = 'The following required parameters are missing from the script template: \''
                    . implode("','", $missingRequiredParameters) . '\'';

                throw new BadRequestException(
                    $error_message
                );
            }
        }

        //Do we want to change the Appliance the version is associated with?
        if ($request->has('appliance_id')) {
            //Validate the appliance exists
            ApplianceController::getApplianceById($request, $request->input('appliance_id'));
        }

        // Update the resource
        $applianceVersion = $this->receiveItem($request, ApplianceVersion::class);

        $errorMessage = 'Unable to update Appliance version.';
        try {
            $applianceVersion->resource->save();
        } catch (\Illuminate\Database\QueryException $exception) {
            // 23000 Error code (Integrity Constraint Violation: version already exists for this application)
            if ($exception->getCode() == 23000) {
                $errorMessage .= ' Version \'' . $request->input('version');
                $errorMessage .= '\' already exists for this appliance.';
                throw new UnprocessableEntityException($errorMessage);
            }

            throw new DatabaseException($errorMessage);
        }
        return $this->responseIdMeta($request, $applianceVersionId, 200);
    }

    /**
     * Delete an appliance version
     * @param Request $request
     * @param $applianceVersionId
     * @return \Illuminate\Http\Response
     * @throws ApplianceActiveException
     * @throws ApplianceVersionNotFoundException
     * @throws DatabaseException
     */
    public function delete(Request $request, $applianceVersionId)
    {
        $rules['appliance_version_id'] = ['required', new IsValidUuid()];
        $request['appliance_version_id'] = $applianceVersionId;
        $this->validate($request, $rules);

        $applianceVersion = static::getApplianceVersionById($request, $applianceVersionId);

        // Don't allow an appliance version to be deleted if it's the laste active version and the appliance active in any pods.
        if ($applianceVersion->appliance->versions->where('appliance_version_active', '=', 'Yes')->count() == 1
            && $applianceVersion->appliance->pods->count() > 0) {
            throw new ApplianceActiveException();
        }

        try {
            $applianceVersion->delete();
        } catch (\Exception $exception) {
            throw new DatabaseException('Failed to delete appliance version record');
        }

        return $this->respondEmpty();
    }

    /**
     * Return a collection of parameters for an appliance version
     * @param Request $request
     * @param $applianceVersionId
     * @return \Illuminate\Http\Response
     * @throws ApplianceVersionNotFoundException
     */
    public function versionParameters(Request $request, $applianceVersionId)
    {
        $applianceVersion = static::getApplianceVersionById($request, $applianceVersionId);

        $parametersQry = ApplianceParametersController::getApplianceParametersQuery($request);
        $parametersQry->where('appliance_version_id', '=', $applianceVersion->id);

        (new QueryTransformer($request))
            ->config(ApplianceParameter::class)
            ->transform($parametersQry);

        $applianceParameters = $parametersQry->paginate($this->perPage);

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
     * Load an appliance version by UUID
     * @param Request $request
     * @param $applianceVersionId
     * @return mixed
     * @throws ApplianceVersionNotFoundException
     */
    public static function getApplianceVersionById(Request $request, $applianceVersionId)
    {
        $applianceVersion = static::getApplianceVersionQuery($request)->find($applianceVersionId);

        if (!is_null($applianceVersion)) {
            return $applianceVersion;
        }

        throw new ApplianceVersionNotFoundException(
            "Appliance version with ID '$applianceVersionId' was not found",
            'id'
        );
    }

    /**
     * Get appliances query builder
     * @param $request
     * @return mixed
     */
    public static function getApplianceVersionQuery($request)
    {
        $applianceVersionQuery = ApplianceVersion::query();

        if ($request->user()->isScoped()) {
            $applianceVersionQuery->where('appliance_version_active', '=', 'Yes');
        }

        return $applianceVersionQuery;
    }
}
