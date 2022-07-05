<?php

namespace Database\Seeders\FastDesk;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Region;
use App\Models\V2\Router;
use App\Models\V2\RouterThroughput;
use App\Models\V2\Vpc;
use App\Models\V2\VpnService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * SetupSeeder is only required for use in Docker to put the same initial resource in
 * play, and to allow us to test the values being used.
 */
class SetupSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run()
    {
        // FastDesk_Mgmt
        VpnService::factory()
            ->for(
                Router::factory()
                    ->for(
                        $routerThroughput = RouterThroughput::factory()
                            ->for(
                                $availabilityZone = AvailabilityZone::factory()
                                    ->for(
                                        $region = Region::factory()
                                            ->create([
                                                'id' => 'reg-a63dd78c',
                                                'name' => 'Manchester',
                                                'is_public' => true,
                                            ])
                                    )->create([
                                        'id' => 'az-4c31a488',
                                        'code' => 'man4',
                                        'name' => 'Manchester South',
                                        'datacentre_site_id' => 5,
                                        'is_public' => true,
                                        'ucs_compute_name' => 'UKF-UCS-1B-FI',
                                        'resource_tier_id' => 'rt-01707d0',
                                    ])
                            )->create([
                                'id' => 'rtp-2d63aa71',
                                'name' => '1Gb',
                                'committed_bandwidth' => 1024,
                                'burst_size' => 1024000,
                            ])
                    )->for(
                        $availabilityZone
                    )->for(
                        Vpc::factory()
                            ->for(
                                $region
                            )
                            ->create([
                                'id' => 'vpc-34fc3361',
                                'name' => 'Fastdesk Management VPC',
                                'reseller_id' => 29789,
                            ])
                    )->create([
                        'id' => 'rtr-27bd74c5',
                        'name' => 'FastDesk Management Router',
                    ])
            )->create([
                'id' => 'vpn-191bd289',
                'name' => 'FastDesk_Mgmt',
            ]);

        // FastDesk_Mgmt
        VpnService::factory()
            ->for(
                Router::factory()
                    ->for(
                        $routerThroughput
                    )->for(
                        $vpc = Vpc::factory()
                            ->for(
                                $region
                            )->create([
                                'id' => 'vpc-218334c3',
                                'name' => 'Fastdesk Client VPC',
                                'reseller_id' => 29789,
                                'console_enabled' => true,
                            ])
                    )->for(
                        $availabilityZone
                    )->create([
                        'id' => 'rtr-686d0751',
                        'name' => 'Fastdesk UKFast Test Shared Client Router',
                        'is_management' => false,
                    ])
            )->create([
                'id' => 'vpn-47da7cbc',
                'name' => 'Fastdesk_Shared_Client',
            ]);

        // vpne_3429_1
        VpnService::factory()
            ->for(
                Router::factory()
                    ->for(
                        RouterThroughput::factory()
                            ->for(
                                $availabilityZone
                            )->create([
                                'id' => 'rtp-1b310f6e',
                                'name' => '25Mb',
                                'committed_bandwidth' => 25,
                                'burst_size' => 2621,
                            ])
                    )->for(
                        $vpc
                    )->for(
                        $availabilityZone
                    )->create([
                        'id' => 'rtr-c8e1a330',
                        'name' => 'Fastdesk Client Shared 3429 Router 1',
                        'is_management' => false,
                    ])
            )->create([
                'id' => 'vpn-890e1ab4',
                'name' => 'vpn_3429_1',
            ]);
    }
}
