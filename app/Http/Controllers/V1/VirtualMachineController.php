<?php

namespace App\Http\Controllers\V1;

use App\Models\V1\VirtualMachine;
use App\Resources\V1\VirtualMachineResource;
use Illuminate\Http\Request;
use UKFast\Api\Exceptions;
use UKFast\DB\Ditto\QueryTransformer;

use App\Kingpin\V1\KingpinService as Kingpin;

class VirtualMachineController extends BaseController
{
    /**
     * List all VM's
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $virtualMachinesQuery = $this->getVirtualMachines();

        (new QueryTransformer($request))
            ->config(VirtualMachine::class)
            ->transform($virtualMachinesQuery);

        return $this->respondCollection(
            $request,
            $virtualMachinesQuery->paginate($this->count)
        );
    }

    /**
     * @param Request $request
     * @param $vmId
     * @return \Illuminate\Http\Response
     * @throws Exceptions\NotFoundException
     */
    public function show(Request $request, $vmId)
    {
        $this->validateVirtualMachineId($request, $vmId);
        $virtualMachines = $this->getVirtualMachines(null, [$vmId]);
        $virtualMachine = $virtualMachines->first();
        if (!$virtualMachine) {
            throw new Exceptions\NotFoundException("Virtual Machine '$vmId' Not Found");
        }

        return $this->respondItem(
            $request,
            $virtualMachine,
            200,
            VirtualMachineResource::class
        );
    }

    /**
     * Get a VM (Model, not query builder - use for updates etc)
     * @param $vmId int ID of the VM to return
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|null|object
     * @throws Exceptions\NotFoundException
     */
    protected function getVirtualMachine($vmId)
    {
        // Load the VM
        $virtualMachineQuery = $this->getVirtualMachines(null, [$vmId]);
        $VirtualMachine = $virtualMachineQuery->first();
        if (!$VirtualMachine) {
            throw new Exceptions\NotFoundException("The Virtual Machine '$vmId' Not Found");
        }
        return $VirtualMachine;
    }

    /**
     * List VM's
     * For admin list all except when $resellerId is passed in
     * @param null $resellerId
     * @param array $vmIds
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function getVirtualMachines($resellerId = null, $vmIds = [])
    {
        $virtualMachineQuery = VirtualMachine::query();
        if (!empty($vmIds)) {
            $virtualMachineQuery->whereIn('servers_id', $vmIds);
        }
        if ($this->is_admin) {
            if (!is_null($resellerId)) {
                $virtualMachineQuery->withResellerId($resellerId);
            }
            // Return ALL VM's
            return $virtualMachineQuery;
        }

        $virtualMachineQuery->where('servers_active', '=', 'y');

        //For non-admin filter on reseller ID
        return $virtualMachineQuery->withResellerId($this->resellerId);
    }

    /**
     * Validates the solution id
     * @param Request $request
     * @param $vmId
     * @return void
     */
    protected function validateVirtualMachineId(&$request, $vmId)
    {
        $request['vm_id'] = $vmId;
        $this->validate($request, ['vm_id' => 'required|integer']);
    }
}
