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
        if ($disks === false) {
            return null;
        }

        $hdds = [];

        if ($disks !== false && count($disks) > 0) {
            foreach ($disks as $disk) {
                $hdd = new \StdClass();
                $hdd->uuid = $disk->uuid;
                $hdd->name = $disk->name;

                // rename disk type to customer friendly wording
                $hdd->type = ($disk->type == 'Flat' ? 'Standard' : 'Cluster');
                $hdd->key = $disk->key;

                $hdd->capacity = $disk->capacity;

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
     * @param array $visible
     * @return array
     */
    public function toArray($request, $visible = [])
    {
        $data = [
            'id' => $this->resource->servers_id,

            'name' => $this->resource->servers_friendly_name,
            'role' => $this->resource->servers_role,

            'hostname' => $this->resource->servers_hostname,
            'computername' => $this->resource->servers_netnios_name,

            'cpu' => $this->resource->servers_cpu,
            'ram' => $this->resource->servers_memory,
            'hdd' => $this->resource->servers_hdd,

            'hdd_disks' => $this->getActiveHDDs(),

            'ip_internal' => $this->resource->ip_internal, //Derived
            'ip_external' => $this->resource->ip_external, //Derived

            'template' => $this->resource->template,
            'platform' => $this->resource->servers_platform,

            'backup' => $this->resource->servers_backup,
            'support' => $this->resource->servers_advanced_support,
            'encrypted' => ($this->resource->servers_encrypted == 'Yes'),

            'status' => $this->resource->servers_status,
            'power_status' => $this->resource->stateCheck(),
            'tools_status' => $this->vmwareToolsStatus(),

            'environment' => $this->resource->servers_ecloud_type,
            'pod_id' => $this->resource->servers_ecloud_datacentre_id,
            'solution_id' => $this->resource->servers_ecloud_ucs_reseller_id,

            'ad_domain_id' => $this->resource->servers_ad_domain_id,
        ];

        if ($this->resource->servers_ecloud_type == 'GPU') {
            $data['gpu_profile'] = $this->resource->servers_ecloud_gpu_profile_uuid;
        }

        // admin only properties
        if ($request->user()->isAdmin()) {
            $data = array_merge($data, [
                'reseller_id' => $this->resource->servers_reseller_id,
                'active' => ($this->resource->servers_active == 'y'),
            ]);
        }

        return $this->filterProperties($request, $data);
    }
}
