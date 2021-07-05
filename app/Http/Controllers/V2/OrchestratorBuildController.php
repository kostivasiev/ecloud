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
        $collection = OrchestratorBuild::forUser(Auth::user());

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
