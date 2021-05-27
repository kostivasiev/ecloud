<?php

namespace App\Http\Controllers\V2;

use App\Models\V2\Task;
use App\Resources\V2\TaskResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use UKFast\DB\Ditto\QueryTransformer;

/**
 * Class TaskController
 * @package App\Http\Controllers\V2
 */
class TaskController extends BaseController
{
    public function index(Request $request, QueryTransformer $queryTransformer)
    {
        $collection = Task::forUser($request->user());

        $queryTransformer->config(Task::class)
            ->transform($collection);

        return TaskResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    public function show(Request $request, string $taskId)
    {
        return new TaskResource(
            Task::forUser($request->user())->findOrFail($taskId)
        );
    }
}
