<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\BuilderConfiguration\StoreRequest;
use App\Http\Requests\V2\BuilderConfiguration\UpdateRequest;
use App\Models\V2\BuilderConfiguration;
use App\Resources\V2\BuilderConfigurationResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use UKFast\DB\Ditto\QueryTransformer;
use UKFast\Responses\UKFastResource;

/**
 * Class BuilderConfigurationController
 * @package App\Http\Controllers\V2
 */
class BuilderConfigurationController extends BaseController
{
    public function index(Request $request, QueryTransformer $queryTransformer)
    {
        $collection = BuilderConfiguration::forUser(Auth::user());

        $queryTransformer->config(BuilderConfiguration::class)
            ->transform($collection);

        return BuilderConfigurationResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    public function show(string $configurationId)
    {
        return new BuilderConfigurationResource(
            BuilderConfiguration::forUser(Auth::user())->findOrFail($configurationId)
        );
    }

    public function store(StoreRequest $request)
    {
        $model = new BuilderConfiguration($request->only([
            'id',
            'reseller_id',
            'employee_id',
            'data',
        ]));
        $model->save();

        return $this->responseIdMeta($request, $model->id, 201);
    }

    public function update(UpdateRequest $request, string $configurationId)
    {
        $model = BuilderConfiguration::forUser(Auth::user())->findOrFail($configurationId);

        $model->fill($request->only([
            'id',
            'reseller_id',
            'employee_id',
            'data',
        ]));
        $model->save();

        return $this->responseIdMeta($request, $model->id, 200);
    }

    public function destroy(string $configurationId)
    {
        $model = BuilderConfiguration::forUser(Auth::user())->findOrFail($configurationId);
        $model->delete();

        return response('', 204);
    }


    public function data(string $configurationId)
    {
        $model = BuilderConfiguration::forUser(Auth::user())->findOrFail($configurationId);

        return response()->json(json_decode($model->data));
    }
}
