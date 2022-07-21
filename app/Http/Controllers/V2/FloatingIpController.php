<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\FloatingIp\AssignRequest;
use App\Http\Requests\V2\FloatingIp\CreateRequest;
use App\Http\Requests\V2\FloatingIp\UpdateRequest;
use App\Jobs\Tasks\FloatingIp\Assign;
use App\Jobs\Tasks\FloatingIp\Unassign;
use App\Models\V2\FloatingIp;
use App\Models\V2\IpAddress;
use App\Models\V2\Nic;
use App\Resources\V2\FloatingIpResource;
use App\Resources\V2\TaskResource;
use App\Support\Resource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\V2\FloatingIpResource as FloatingIpResourcePivot;
use Illuminate\Support\Facades\Cache;

/**
 * Class InstanceController
 * @package App\Http\Controllers\V2
 */
class FloatingIpController extends BaseController
{
    public function index(Request $request)
    {
        $collection = FloatingIp::forUser($request->user());

        return FloatingIpResource::collection(
            $collection->search()
                ->paginate(
                    $request->input('per_page', env('PAGINATION_LIMIT'))
                )
        );
    }

    public function show(Request $request, string $fipId)
    {
        return new FloatingIpResource(
            FloatingIp::forUser($request->user())->findOrFail($fipId)
        );
    }

    public function store(CreateRequest $request)
    {
        $floatingIp = new FloatingIp(
            $request->only(['vpc_id', 'name', 'availability_zone_id', 'rdns_hostname'])
        );

        $task = $floatingIp->syncSave();
        return $this->responseIdMeta($request, $floatingIp->id, 202, $task->id);
    }

    public function update(UpdateRequest $request, string $fipId)
    {
        $floatingIp = FloatingIp::forUser(Auth::user())->findOrFail($fipId);
        $floatingIp->fill($request->only(['name', 'rdns_hostname']));

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

        $resourceId = $request->resource_id;

        /**
         * @deprecated To be removed, we will want to completely prevent assignment of fips to NICS
         */
        if (Resource::classFromId($resourceId) == Nic::class) {
            $resourceId = Nic::forUser($request->user())->findOrFail($resourceId)
                ->ipAddresses()->withType(IpAddress::TYPE_DHCP)->first()->getKey();
        }

        $task = $floatingIp->createTaskWithLock(
            Assign::TASK_NAME,
            Assign::class,
            ['resource_id' => $resourceId]
        );

        return $this->responseIdMeta($request, $floatingIp->id, 202, $task->id);
    }

    public function unassign(Request $request, string $fipId)
    {
        $floatingIp = FloatingIp::forUser($request->user())->findOrFail($fipId);

        $task = $floatingIp->createTaskWithLock(
            Unassign::TASK_NAME,
            Unassign::class
        );

        return $this->responseIdMeta($request, $floatingIp->id, 202, $task->id);
    }

    public function tasks(Request $request, string $fipId)
    {
        $collection = FloatingIp::forUser($request->user())->findOrFail($fipId)->tasks();

        return TaskResource::collection($collection->search()->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }
}
