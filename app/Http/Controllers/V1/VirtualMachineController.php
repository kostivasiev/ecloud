<?php

namespace App\Http\Controllers\V1;

use App\Models\V1\VirtualMachine;
use App\Resources\V1\VirtualMachineResource;

use Illuminate\Http\Request;
use UKFast\Api\Exceptions;

use UKFast\DB\Ditto\QueryTransformer;

use App\Kingpin\V1\KingpinService as Kingpin;
use App\Exceptions\V1\KingpinException;

use App\Services\IntapiService;
use App\Exceptions\V1\IntapiServiceException;

use App\Exceptions\V1\ServiceTimeoutException;
use App\Exceptions\V1\ServiceUnavailableException;

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
            $virtualMachinesQuery->paginate($this->perPage)
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
     * @param Request $request
     * @param IntapiService $intapiService
     * @param $vmId
     * @return \Illuminate\Http\Response
     * @throws Exceptions\ForbiddenException
     * @throws ServiceUnavailableException
     */
    public function destroy(Request $request, IntapiService $intapiService, $vmId)
    {
        $this->validateVirtualMachineId($request, $vmId);
        $virtualMachine = $this->getVirtualMachines($request->user->resellerId)->find($vmId);

        //cant delete vm if its doing something that requires it to exist
        if (!$virtualMachine->canBeDeleted()) {
            throw new Exceptions\ForbiddenException(
                'VM cannot be deleted with status of: ' . $virtualMachine->servers_status
            );
        }

        //server is in contract
        if (!$request->user->isAdmin && $virtualMachine->inContract()) {
            throw new Exceptions\ForbiddenException(
                'VM cannot be deleted, in contract until ' .
                date('d/m/Y', strtotime($virtualMachine->servers_contract_end_date))
            );
        }

        //server is a managed device
        if (!$request->user->isAdmin && $virtualMachine->isManaged()) {
            throw new Exceptions\ForbiddenException(
                'VM cannot be deleted, device is managed by UKFast'
            );
        }

        //schedule automation
        try {
            $automationRequestId = $intapiService->automationRequest(
                'delete_vm',
                'server',
                $virtualMachine->getKey(),
                [
                    'template_type' => 'solution',
                ],
                'ecloud_ucs_' . $virtualMachine->pod->getKey(),
                $request->user->applicationId
            );
        } catch (IntapiServiceException $exception) {
            throw new ServiceUnavailableException('Unable to schedule deletion request');
        }

        $virtualMachine->servers_status = 'Pending Deletion';
        if (!$virtualMachine->save()) {
            //Log::critical('');
        }

        if (!$request->user->isAdmin) {
            $headers = [
                'X-AutomationRequestId' => $automationRequestId
            ];
        }

        return $this->respondEmpty(202, $headers);
    }


    /**
     * @param Request $request
     * @param $vmId
     * @throws Exceptions\NotFoundException
     * @throws KingpinException
     * @throws ServiceTimeoutException
     */
    public function powerOff(Request $request, $vmId)
    {
        $this->validateVirtualMachineId($request, $vmId);
        $virtualMachine = $this->getVirtualMachine($vmId);

        $result = $this->shutDownVirtualMachine($virtualMachine);
        if (!$result) {
            $errorMessage = 'Failed to power off virtual machine';
            throw new KingpinException($errorMessage);
        }

        $this->respondEmpty();
    }

    /**
     * Power on a virtual machine
     * @param Request $request
     * @param $vmId
     * @throws Exceptions\NotFoundException
     * @throws KingpinException
     */
    public function powerOn(Request $request, $vmId)
    {
        $this->validateVirtualMachineId($request, $vmId);
        $virtualMachine = $this->getVirtualMachine($vmId);

        $result = $this->powerOnVirtualMachine($virtualMachine);
        if (!$result) {
            throw new KingpinException('Failed to power on virtual machine');
        }

        $this->respondEmpty();
    }

    /**
     * Power-cycle the virtual machine - Power off then on again.
     * @param Request $request
     * @param $vmId
     * @throws Exceptions\NotFoundException
     * @throws KingpinException
     * @throws ServiceTimeoutException
     */
    public function powerCycle(Request $request, $vmId)
    {
        $this->validateVirtualMachineId($request, $vmId);
        $virtualMachine = $this->getVirtualMachine($vmId);
        //Power down
        $shutDownResult = $this->shutDownVirtualMachine($virtualMachine);
        if (!$shutDownResult) {
            throw new KingpinException('Failed to power down virtual machine');
        }
        sleep(3);
        //Power up
        $powerOnResult = $this->powerOnVirtualMachine($virtualMachine);
        if (!$powerOnResult) {
            throw new KingpinException('Failed to power on virtual machine');
        }

        $this->respondEmpty();
    }


    /**
     * Power on a virtual machine
     * @param VirtualMachine $virtualMachine
     * @return bool
     * @throws KingpinException
     */
    protected function powerOnVirtualMachine(VirtualMachine $virtualMachine)
    {
        $kingpin = $this->loadKingpinService($virtualMachine);

        $powerOnResult = $kingpin->powerOnVirtualMachine(
            $virtualMachine->getKey(),
            $virtualMachine->solutionId()
        );

        if (!$powerOnResult) {
            return false;
        }

        return true;
    }

    /**
     * @param VirtualMachine $virtualMachine
     * @return bool
     * @throws KingpinException
     * @throws ServiceTimeoutException
     */
    protected function shutDownVirtualMachine(VirtualMachine $virtualMachine)
    {
        $kingpin = $this->loadKingpinService($virtualMachine);

        $shutDownResult = $kingpin->shutDownVirtualMachine(
            $virtualMachine->getKey(),
            $virtualMachine->solutionId()
        );

        if (!$shutDownResult) {
            return false;
        }

        $startTime = time();

        do {
            sleep(10);
            $isOnline = $kingpin->checkVMOnline($virtualMachine->getKey(), $virtualMachine->solutionId());
            if ($isOnline === false) {
                return true;
            }
        } while (time() - $startTime < 120);

        throw new ServiceTimeoutException('Timeout waiting for Virtual Machine to power off.');
    }

    /**
     * Load and configure the Kingpin service for a Virtual Machine
     * @param VirtualMachine $virtualMachine
     * @return mixed
     * @throws KingpinException
     */
    protected function loadKingpinService(VirtualMachine $virtualMachine)
    {
        try {
            $kingpin = app()->makeWith(
                'App\Kingpin\V1\KingpinService',
                [$virtualMachine->getPod(), $virtualMachine->type()]
            );
        } catch (\Exception $exception) {
            throw new KingpinException('Unable to connect to Virtual Machine');
        }

        return $kingpin;
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
        if ($this->isAdmin) {
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
