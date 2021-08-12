<?php

namespace App\Http\Controllers\V2;

use App\Models\V2\OrchestratorBuild;
use App\Resources\V2\OrchestratorBuildResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use UKFast\DB\Ditto\QueryTransformer;

/**
 * Class OrchestratorBuildController
 * @package App\Http\Controllers\V2
 */
class OrchestratorBuildController extends BaseController
{
    public function index(Request $request, QueryTransformer $queryTransformer)
    {
        if ($request->hasAny([
            'orchestrator_config_id',
            'orchestrator_config_id:eq',
            'orchestrator_config_id:neq',
        ])) {
            $buildIds = OrchestratorBuild::forUser(Auth::user())->get();

            if ($request->has('orchestrator_config_id') || $request->has('orchestrator_config_id:eq')) {
                if ($request->has('orchestrator_config_id')) {
                    $orchestratorConfigId = $request->get('orchestrator_config_id');
                    $request->query->remove('orchestrator_config_id');
                } else {
                    $orchestratorConfigId = $request->get('orchestrator_config_id:eq');
                    $request->query->remove('orchestrator_config_id:eq');
                }
                $buildIds = $buildIds->reject(function ($orchestratorBuild) use ($orchestratorConfigId) {
                    return $orchestratorBuild->orchestrator_config_id != $orchestratorConfigId;
                });
            }

            if ($request->has('orchestrator_config_id:neq')) {
                $orchestratorConfigId = $request->get('orchestrator_config_id:neq');
                $request->query->remove('orchestrator_config_id:neq');
                $buildIds = $buildIds->reject(function ($orchestratorBuild) use ($orchestratorConfigId) {
                    return $orchestratorBuild->orchestrator_config_id == $orchestratorConfigId;
                });
            }

            $collection = OrchestratorBuild::whereIn('id', $buildIds->map(function ($orchestratorBuild) {
                return $orchestratorBuild->id;
            }));
        } else {
            $collection = OrchestratorBuild::forUser(Auth::user());
        }

        $queryTransformer->config(OrchestratorBuild::class)
            ->transform($collection);

        return OrchestratorBuildResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    public function show(string $orchestratorBuildId)
    {
        return new OrchestratorBuildResource(
            OrchestratorBuild::forUser(Auth::user())->findOrFail($orchestratorBuildId)
        );
    }
}
