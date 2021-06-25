<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\OrchestratorConfig\StoreRequest;
use App\Http\Requests\V2\OrchestratorConfig\UpdateRequest;
use App\Models\V2\OrchestratorConfig;
use App\Resources\V2\OrchestratorConfigResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use UKFast\DB\Ditto\QueryTransformer;

/**
 * Class OrchestratorConfigController
 * @package App\Http\Controllers\V2
 */
class OrchestratorConfigController extends BaseController
{
    public function index(Request $request, QueryTransformer $queryTransformer)
    {
        $collection = OrchestratorConfig::forUser(Auth::user());

        $queryTransformer->config(OrchestratorConfig::class)
            ->transform($collection);

        return OrchestratorConfigResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    public function show(string $orchestratorConfigId)
    {
        return new OrchestratorConfigResource(
            OrchestratorConfig::forUser(Auth::user())->findOrFail($orchestratorConfigId)
        );
    }

    public function store(StoreRequest $request)
    {
        $model = new OrchestratorConfig($request->only([
            'id',
            'reseller_id',
            'employee_id',
        ]));
        $model->save();

        return $this->responseIdMeta($request, $model->id, 201);
    }

    public function update(UpdateRequest $request, string $orchestratorConfigId)
    {
        $model = OrchestratorConfig::forUser(Auth::user())->findOrFail($orchestratorConfigId);

        $model->fill($request->only([
            'id',
            'reseller_id',
            'employee_id',
        ]));
        $model->save();

        return $this->responseIdMeta($request, $model->id, 200);
    }

    public function destroy(string $orchestratorConfigId)
    {
        $model = OrchestratorConfig::forUser(Auth::user())->findOrFail($orchestratorConfigId);
        $model->delete();

        return response('', 204);
    }

    public function showData(string $orchestratorConfigId)
    {
        $model = OrchestratorConfig::forUser(Auth::user())->findOrFail($orchestratorConfigId);

        return response()->json(json_decode($model->data));
    }

    public function storeData(Request $request, string $orchestratorConfigId)
    {
        $model = OrchestratorConfig::forUser(Auth::user())->findOrFail($orchestratorConfigId);
        $model->data = $request->getContent();
        $model->save();

        return response('', 200);
    }
}
