<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\V2\OrchestratorConfig;

$factory->define(OrchestratorConfig::class, function () {
    $data = <<<EOM
            {
                "vpcs": [
                    {
                        "name": "vpc-1",
                        "region_id": "reg-test"
                    },
                    {
                        "name": "vpc-2",
                        "region_id": "reg-test",
                        "console_enabled": true,
                        "advanced_networking": true,
                        "support": true
                    }
                ],
                "routers": [
                    {
                        "vpc_id": "{vpc.0}",
                        "name": "router-1"
                    },
                    {
                        "vpc_id": "{vpc.1}",
                        "name": "router-2",
                        "router_throughput_id": "rtp-ec393951",
                        "configure_default_policies": true
                    }
                ],
                "networks": [
                    {
                        "router_id": "{router.0}",
                        "name": "network-1"
                    },
                    {
                        "router_id": "{router.1}",
                        "name": "network-2",
                        "subnet": "10.0.0.0\/24"
                    }
                ],
                "instances": [
                    {
                        "name": "Builder Test Instance",
                        "vpc_id": "{vpc.0}",
                        "image_id": "img-aaaaaaaa",
                        "vcpu_cores": 1,
                        "ram_capacity": 1024,
                        "locked": false,
                        "backup_enabled": false,
                        "requires_floating_ip": true,
                        "volume_capacity": 50,
                        "volume_iops": 300
                    }
                ]
            }
EOM;

    return [
        'reseller_id' => 1,
        'employee_id' => 1,
        'data' => $data
    ];
});


