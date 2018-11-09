<?php

namespace App\Resources\V1;

use UKFast\Api\Resource\CustomResource;

class VirtualMachineResource extends CustomResource
{

    public function __construct($resource)
    {
        parent::__construct($resource);
    }

    //TODO: This is how the old API does it, but it may make sense to just do one call to getVM()
    /**
     * Check the state of the VM
     * @return mixed
     */
    protected function stateCheck()
    {
        if ($this->resource->servers_status != 'Complete') {
            return $this->resource->servers_status;
        }

        return $this->resource->stateCheck();
    }

    /**
     * Get the VMWare tools status for the VM
     * @return string
     */
    protected function vmwareToolsStatus()
    {
        $vmwareToolsStatus = $this->resource->vmwareToolsStatus();
        if ($vmwareToolsStatus === false) {
            return 'Unknown';
        }
        return $vmwareToolsStatus;
    }

    /**
     * Get active HDD's
     * @return array
     */
    protected function getActiveHDDs()
    {
        $disks = $this->resource->getActiveHDDs();

        $hdds = [];

        if ($disks === false) {
            return 'Unknown';
        }

        if ($disks !== false && count($disks) > 0) {
            foreach ($disks as $disk) {
                $hdd = new \StdClass();
                $hdd->name = $disk->name;
                $hdd->capacity_gb = $disk->capacity;
                $hdds[] = $hdd;
            }
        }

        return $hdds;
    }


    /**
     * Transform the data resource into an array.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request, $visible = [])
    {
        $data = [
            'id' => $this->resource->servers_id,
            'name' => $this->resource->servers_friendly_name,
            'cpu' => $this->resource->servers_cpu,
            'ram_gb' => $this->resource->servers_memory,
            'hdd_gb' => $this->resource->servers_hdd,
            'platform' => $this->resource->servers_platform,
            'operating_system' => $this->resource->server_license_friendly_name, //Relation
            'backup' => $this->resource->servers_backup,
            'support' => $this->resource->servers_advanced_support,
            'ip_internal' => $this->resource->ip_internal, //Derived
            'ip_external' => $this->resource->ip_external, //Derived
            'type' => $this->resource->servers_ecloud_type,
            'hostname' => $this->resource->servers_hostname,
            'computername' => $this->resource->servers_netnios_name,
            'status' => $this->stateCheck(),
            'vmware-tools' => $this->vmwareToolsStatus(),
            'hdd_disk' => $this->getActiveHDDs(),
            'solution_id' => $this->resource->servers_ecloud_ucs_reseller_id
        ];

        return $this->filterProperties($request, $data);
    }
}
