<?php

namespace App\Http\Controllers\V2;

use App\Models\V2\Task;
use App\Resources\V2\TaskResource;
use Illuminate\Http\Request;

/**
 * Class TaskController
 * @package App\Http\Controllers\V2
 */
class TaskController extends BaseController
{
    public function index(Request $request)
    {
        $collection = Task::forUser($request->user());

        return TaskResource::collection($collection->search()->paginate(
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
