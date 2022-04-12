<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\CreateNicRequest;
use App\Http\Requests\V2\Nic\AssociateIpRequest;
use App\Http\Requests\V2\Nic\CreateRequest;
use App\Http\Requests\V2\Nic\UpdateRequest;
use App\Http\Requests\V2\UpdateNicRequest;
use App\Models\V2\IpAddress;
use App\Models\V2\Nic;
use App\Resources\V2\IpAddressResource;
use App\Resources\V2\NicResource;
use App\Resources\V2\TaskResource;
use App\Tasks\Nic\AssociateIp;
use App\Tasks\Nic\DisassociateIp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NicController extends BaseController
{
    public function index(Request $request)
    {
        $collection = Nic::forUser($request->user());

        return NicResource::collection($collection->search()->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    public function show(Request $request, string $nicId)
    {
        return new NicResource(
            Nic::forUser($request->user())->findOrFail($nicId)
        );
    }

    public function create(CreateRequest $request)
    {
        $nic = new Nic($request->only([
            'name',
            'mac_address',
            'instance_id',
            'network_id',
        ]));

        $task = $nic->syncSave();

        return $this->responseIdMeta($request, $nic->id, 202, $task->id);
    }

    public function update(UpdateRequest $request, string $nicId)
    {
        $nic = Nic::forUser(Auth::user())->findOrFail($nicId);
        $nic->fill($request->only([
            'name',
        ]));

        $task = $nic->syncSave();

        return $this->responseIdMeta($request, $nic->id, 202, $task->id);
    }

    public function destroy(Request $request, string $nicId)
    {
        $nic = Nic::forUser($request->user())->findOrFail($nicId);

        if (!$nic->canDelete()) {
            return $nic->getDeletionError();
        }

        $task = $nic->syncDelete();
        return $this->responseTaskId($task->id);
    }

    public function tasks(Request $request, string $nicId)
    {
        $collection = Nic::forUser($request->user())->findOrFail($nicId)->tasks();

        return TaskResource::collection($collection->search()->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    public function ipAddresses(Request $request, string $nicId)
    {
        $collection = Nic::forUser($request->user())
            ->findOrFail($nicId)
            ->ipAddresses()
            ->sortByIp();

        return IpAddressResource::collection($collection->search()->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    public function associateIpAddress(AssociateIpRequest $request, string $nicId)
    {
        $nic = Nic::forUser(Auth::user())->findOrFail($nicId);

        $task = $nic->createTaskWithLock(
            AssociateIp::$name,
            AssociateIp::class,
            [
                'ip_address_id' => $request->input('ip_address_id')
            ]
        );

        return $this->responseTaskId($task->id);
    }

    public function disassociateIpAddress(Request $request, string $nicId, string $ipAddressId)
    {
        $nic = Nic::forUser(Auth::user())->findOrFail($nicId);
        $ipAddress = IpAddress::forUser(Auth::user())->findOrFail($ipAddressId);

        $task = $nic->createTaskWithLock(
            DisassociateIp::$name,
            DisassociateIp::class,
            [
                'ip_address_id' => $ipAddress->id
            ]
        );

        return $this->responseTaskId($task->id);
    }
}
