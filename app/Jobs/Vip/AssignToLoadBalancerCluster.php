<?php

namespace App\Jobs\Vip;

use App\Jobs\TaskJob;

class AssignToLoadBalancerCluster extends TaskJob
{
    public function handle()
    {
        $vip = $this->task->resource;

// https://gitlab.devops.ukfast.co.uk/ukfast/api.ukfast/loadbalancers/-/blob/master/openapi.yaml

        //POST  /loadbalancers/v2/clusters/{clusterId}/vips

/**
 * {
"internal_cidr": "192.10.110.1/32",
"external_cidr": "172.10.110.1/32",
"mac_address": "01:23:45:67:89:ab"
}
 */

    }
}
