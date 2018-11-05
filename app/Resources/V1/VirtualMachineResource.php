<?php

namespace App\Resources\V1;

use UKFast\Api\Resource\CustomResource;

class VirtualMachineResource extends CustomResource
{
    /**
     * Transform the data resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request, $visible = [])
    {
        $data = [
            'id' => $this->resource->servers_id,
            'name' => $this->resource->servers_friendly_name,
            'cpu' => $this->resource->servers_cpu,
            'ram' => $this->resource->servers_memory,
            'hdd' => $this->resource->servers_hdd,
            'platform' => $this->resource->servers_platform,
            'operating_system' => $this->resource->server_license_friendly_name, //Relation
            'backup' => $this->resource->servers_backup,
            'support' => $this->resource->servers_advanced_support,
            'ip_internal' => $this->resource->ip_internal, //Derived
            'ip_external' => $this->resource->ip_external, //Derived
            'type' => $this->resource->servers_ecloud_type,
            'hostname' => $this->resource->servers_hostname,
            'computername' => $this->resource->servers_netnios_name,
            //TODO: Status is to be determined via Kingpin
            'status' => $this->resource->servers_status,
            //TODO: hdd_disk is to be determined via Kingpin
            'solution_id' => $this->resource->servers_ecloud_ucs_reseller_id
        ];

        return $this->filterProperties($request, $data);
    }
}
