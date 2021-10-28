<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\FloatingIp\AssignRequest;
use App\Http\Requests\V2\FloatingIp\CreateRequest;
use App\Http\Requests\V2\FloatingIp\UpdateRequest;
use App\Models\V2\AvailabilityZone;
use App\Models\V2\FloatingIp;
use App\Models\V2\Task;
use App\Models\V2\Vpc;
use App\Resources\V2\FloatingIpResource;
use App\Resources\V2\TaskResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use UKFast\DB\Ditto\QueryTransformer;

/**
 * Class InstanceController
 * @package App\Http\Controllers\V2
 */
class FloatingIpController extends BaseController
{
    public function index(Request $request, QueryTransformer $queryTransformer)
    {
        $collection = FloatingIp::forUser($request->user());

        $queryTransformer->config(FloatingIp::class)
            ->transform($collection);

        return FloatingIpResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    public function show(Request $request, string $fipId)
    {
        return new FloatingIpResource(
            FloatingIp::forUser($request->user())->findOrFail($fipId)
        );
    }

    public function store(CreateRequest $request)
    {
//        $availabilityZone = AvailabilityZone::forUser(Auth::user())
//            ->findOrFail($request->availability_zone_id)
//            ->region_id;
//        $vpc = Vpc::forUser(Auth::user())->findOrFail($request->vpc_id)->region_id;
//
//        if ($availabilityZone !== $vpc) {
//            return response()->json([
//                'errors' => [
//                    'title' => 'Not Found',
//                    'detail' => 'The specified availability zone is not available to that VPC',
//                    'status' => 404,
//                    'source' => 'availability_zone_id'
//                ]
//            ], 404);
//        }

        $floatingIp = new FloatingIp(
            $request->only(['vpc_id', 'name', 'availability_zone_id'])
        );

        $task = $floatingIp->syncSave();
        return $this->responseIdMeta($request, $floatingIp->id, 202, $task->id);
    }

    public function update(UpdateRequest $request, string $fipId)
    {
        $floatingIp = FloatingIp::forUser(Auth::user())->findOrFail($fipId);
        $floatingIp->fill($request->only(['name']));

        $task = $floatingIp->syncSave();
        return $this->responseIdMeta($request, $floatingIp->id, 202, $task->id);
    }

    public function destroy(Request $request, string $fipId)
    {
        $floatingIp = FloatingIp::forUser($request->user())->findOrFail($fipId);

        $task = $floatingIp->syncDelete();
        return $this->responseTaskId($task->id);
    }

    public function assign(AssignRequest $request, string $fipId)
    {
        $floatingIp = FloatingIp::forUser($request->user())->findOrFail($fipId);

        $task = $floatingIp->createTaskWithLock(
            'floating_ip_assign',
            \App\Jobs\Tasks\FloatingIp\Assign::class,
            ['resource_id' => $request->resource_id]
        );

        return $this->responseIdMeta($request, $floatingIp->id, 202, $task->id);
    }

    public function unassign(Request $request, string $fipId)
    {
        $floatingIp = FloatingIp::forUser($request->user())->findOrFail($fipId);

        $task = $floatingIp->createTaskWithLock('floating_ip_unassign', \App\Jobs\Tasks\FloatingIp\Unassign::class);

        return $this->responseIdMeta($request, $floatingIp->id, 202, $task->id);
    }

    public function tasks(Request $request, QueryTransformer $queryTransformer, string $fipId)
    {
        $collection = FloatingIp::forUser($request->user())->findOrFail($fipId)->tasks();
        $queryTransformer->config(Task::class)
            ->transform($collection);

        return TaskResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }
}
