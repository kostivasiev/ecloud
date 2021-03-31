<?php

namespace Tests\Mocks\Traits;

use UKFast\Admin\Networking\AdminClient;
use UKFast\Admin\Networking\IpRangeClient;

trait NetworkingApio
{
    public function networkingApioSetup()
    {
        app()->bind(AdminClient::class, function () {
            $mockAdminClient = \Mockery::mock(AdminClient::class);
            $mockAdminClient->shouldReceive('ipRanges')->andReturnSelf();
            $mockAdminClient->shouldReceive('getPage')->andReturnUsing(function () {
                $mockIpRangeClient = \Mockery::mock(IpRangeClient::class)->makePartial();
                $mockIpRangeClient->shouldReceive('totalPages')->andReturn(1);
                $mockIpRangeClient->shouldReceive('getItems')->andReturn(
                    [
                        new \UKFast\Admin\Networking\Entities\IpRange(
                            [
                                'id' => 1228,
                                'description' => '78.109.172.208\/29 Spilt Ink Temp IP in Man4',
                                'external_subnet' => '255.255.255.248',
                                'internal_subnet' => '',
                                'dns_one' => '94.229.163.244',
                                'dns_two' => '81.201.138.244',
                                'vlan' => '512,516,517,639,1131,1132,1917,1921',
                                'ipv6' => null,
                                'ipv6_subnet' => '',
                                'ipv6_gateway' => '',
                                'ipv6_dns_one' => '',
                                'ipv6_dns_two' => '',
                                'auto_deploy_environment' => 'ecloud nsx',
                                'auto_deploy_firewall_id' => 0,
                                'auto_deploy_datacentre_id' => 14,
                                'reseller_id' => 0,
                                'parent_range_id' => 0,
                                'network_address' => 1315810512,
                                'cidr' => 29,
                                'type' => 'External',
                                'vrf_number' => 0
                            ]
                        )
                    ]
                );
                return $mockIpRangeClient;
            });
            return $mockAdminClient;
        });
    }
}