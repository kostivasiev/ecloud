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
        $collection = Task::query();

        $queryTransformer->config(Task::class)
            ->transform($collection);

        return TaskResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    public function show(Request $request, string $taskId)
    {
        $task = Task::findOrFail($taskId);
        $taskResource = $task->resource()->withTrashed()->forUser($request->user())->first();
        if (!$taskResource) {
            throw (new ModelNotFoundException)->setModel(Task::class, $taskId);
        }

        return new TaskResource(
            $taskResource
        );
    }
}
