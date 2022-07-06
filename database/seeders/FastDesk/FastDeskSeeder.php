<?php

namespace Database\Seeders\FastDesk;

use App\Models\V2\FloatingIp;
use App\Models\V2\FloatingIpResource;
use App\Models\V2\Vpc;
use App\Models\V2\VpnEndpoint;
use App\Models\V2\VpnProfileGroup;
use App\Models\V2\VpnService;
use App\Models\V2\VpnSession;
use App\Models\V2\VpnSessionNetwork;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

class FastDeskSeeder extends Seeder {

    use WithoutModelEvents;

    public function run()
    {
        $fastDeskMgmt = VpnService::find('vpn-191bd289');
        $fastDeskShared = VpnService::find('vpn-47da7cbc');
        $vpn34291 = VpnService::find('vpn-890e1ab4');

        // FastDesk Management - eCloud VPC
        FloatingIpResource::factory()
            ->assignedTo(
                FloatingIp::factory()
                    ->for(Vpc::find('vpc-218334c3'))
                    ->create([
                        'id' => 'fip-1d93e06e',
                        'ip_address' => '45.131.138.9',
                    ]),
                VpnEndpoint::factory()
                    ->for($fastDeskMgmt)
                    ->create([
                        'id' => $this->addCustomKey(VpnEndpoint::class),
                        'name' => 'FastDesk Management - eCloud VPC'
                    ])
            )->create([
                'id' => $this->addCustomKey(FloatingIpResource::class),
            ]);


        // FastDesk Management -> Fastdesk Old
        FloatingIpResource::factory()
            ->assignedTo(
                FloatingIp::factory()
                    ->for(Vpc::find('vpc-34fc3361'))
                    ->create([
                        'id' => 'fip-727faf58',
                        'ip_address' => '45.131.138.5',
                    ]),
                $vpnEndpointFastDeskMgmtOld = VpnEndpoint::factory()
                    ->for($fastDeskMgmt)
                    ->create([
                        'id' => $this->addCustomKey(VpnEndpoint::class),
                        'name' => 'FastDesk Management -> Fastdesk Old'
                    ])
            )->create([
                'id' => $this->addCustomKey(FloatingIpResource::class),
            ]);

        // Fastdesk Shared Client - eCloud VPC
        FloatingIpResource::factory()
            ->assignedTo(
                FloatingIp::factory()
                    ->for(Vpc::find('vpc-34fc3361'))
                    ->create([
                        'id' => 'fip-495722a7',
                        'ip_address' => '45.131.138.10',
                    ]),
                VpnEndpoint::factory()
                    ->for($fastDeskShared)
                    ->create([
                        'id' => $this->addCustomKey(VpnEndpoint::class),
                        'name' => 'Fastdesk Shared Client - eCloud VPC'
                    ])
            )->create([
                'id' => $this->addCustomKey(FloatingIpResource::class),
            ]);

        // vpne_3429_1
        FloatingIpResource::factory()
            ->assignedTo(
                FloatingIp::factory()
                    ->for(Vpc::find('vpc-218334c3'))
                    ->create([
                        'id' => 'fip-84e34f0c',
                        'ip_address' => '45.131.138.179',
                    ]),
                $vpnEndpointVpn34291 = VpnEndpoint::factory()
                    ->for($vpn34291)
                    ->create([
                        'id' => $this->addCustomKey(VpnEndpoint::class),
                        'name' => 'vpne_3429_1'
                    ])
            )->create([
                'id' => $this->addCustomKey(FloatingIpResource::class),
            ]);

        $vpnProfileGroup = VpnProfileGroup::find('vpnpg-690b45e7');

        // <-- 3. Fastdesk Mgmt -> Fastdesk Shared Client Start -->
        $vpnSession = VpnSession::factory()
            ->for($vpnProfileGroup)
            ->for($fastDeskMgmt)
            ->for(
                $vpnEndpoint = VpnEndpoint::factory()
                    ->for($fastDeskMgmt)
                    ->create([
                        'id' => $this->addCustomKey(VpnEndpoint::class),
                        'name' => 'FastDesk Management - eCloud VPC',
                    ])
            )->create([
                'id' => $this->addCustomKey(VpnSession::class),
                'name' => '3. Fastdesk Mgmt -> Fastdesk Shared Client',
                'remote_ip' => '45.131.138.10',
            ]);

        foreach (explode(',', '172.31.134.0/25,172.31.134.128/25,172.31.135.128/25,172.31.139.4/32') as $localNetwork) {
            VpnSessionNetwork::factory()
                ->for($vpnSession)
                ->create([
                    'id' => $this->addCustomKey(VpnSessionNetwork::class),
                    'type' => 'local',
                    'ip_address' => $localNetwork,
                ]);
        }

        VpnSessionNetwork::factory()
            ->for($vpnSession)
            ->create([
                'id' => $this->addCustomKey(VpnSessionNetwork::class),
                'type' => 'remote',
                'ip_address' => '10.10.0.0/24',
            ]);
        // <-- 3. Fastdesk Mgmt -> Fastdesk Shared Client End -->

        // <-- 3. Fastdesk Shared Client -> Fastdesk Mgmt Start -->
        $vpnSession = VpnSession::factory()
            ->for($vpnProfileGroup)
            ->for($fastDeskShared)
            ->for($vpnEndpoint)
            ->create([
                'id' => $this->addCustomKey(VpnSession::class),
                'name' => '3. Fastdesk Shared Client -> Fastdesk Mgmt',
                'remote_ip' => '45.131.138.9',
            ]);

        VpnSessionNetwork::factory()
            ->for($vpnSession)
            ->create([
                'id' => $this->addCustomKey(VpnSessionNetwork::class),
                'type' => 'local',
                'ip_address' => '10.10.0.0/24',
            ]);

        foreach (explode(',', '172.31.134.0/25,172.31.134.128/25,172.31.135.128/25,172.31.139.4/32') as $remoteNetwork) {
            VpnSessionNetwork::factory()
                ->for($vpnSession)
                ->create([
                    'id' => $this->addCustomKey(VpnSessionNetwork::class),
                    'type' => 'remote',
                    'ip_address' => $remoteNetwork,
                ]);
        }
        // <-- 3. Fastdesk Shared Client -> Fastdesk Mgmt End -->

        // <-- 5. Fastdesk Mgmt -> vpne_29789_1 Start -->
        $vpnSession = VpnSession::factory()
            ->for($vpnProfileGroup)
            ->for($fastDeskMgmt)
            ->for($vpnEndpoint)
            ->create([
                'id' => $this->addCustomKey(VpnSession::class),
                'name' => '5. Fastdesk Mgmt -> vpne_29789_1',
                'remote_ip' => '45.131.138.222',
            ]);

        foreach (explode(',', '172.31.139.4/32,172.31.142.4/32') as $localNetwork) {
            VpnSessionNetwork::factory()
                ->for($vpnSession)
                ->create([
                    'id' => $this->addCustomKey(VpnSessionNetwork::class),
                    'type' => 'local',
                    'ip_address' => $localNetwork,
                ]);
        }

        VpnSessionNetwork::factory()
            ->for($vpnSession)
            ->create([
                'id' => $this->addCustomKey(VpnSessionNetwork::class),
                'type' => 'remote',
                'ip_address' => '10.10.0.0/24',
            ]);
        // <-- 5. Fastdesk Mgmt -> vpne_29789_1 End -->

        // <-- 7. FastDesk Management - eCloud VPC > vpne_3429_1 Start -->
        $vpnSession = VpnSession::factory()
            ->for($vpnProfileGroup)
            ->for($fastDeskMgmt)
            ->for($vpnEndpoint)
            ->create([
                'id' => $this->addCustomKey(VpnSession::class),
                'name' => '7. FastDesk Management - eCloud VPC > vpne_3429_1',
                'remote_ip' => '45.131.138.179',
            ]);

        foreach (explode(',', '172.31.134.0/25,172.31.134.128/25,172.31.135.128/25,172.31.139.4/32') as $localNetwork) {
            VpnSessionNetwork::factory()
                ->for($vpnSession)
                ->create([
                    'id' => $this->addCustomKey(VpnSessionNetwork::class),
                    'type' => 'local',
                    'ip_address' => $localNetwork,
                ]);
        }

        VpnSessionNetwork::factory()
            ->for($vpnSession)
            ->create([
                'id' => $this->addCustomKey(VpnSessionNetwork::class),
                'type' => 'remote',
                'ip_address' => '10.10.4.0/24',
            ]);
        // <-- 7. FastDesk Management - eCloud VPC > vpne_3429_1 End -->

        // <-- 7. vpne_3429_1 > FastDesk Management - eCloud VPC Start -->
        $vpnSession = VpnSession::factory()
            ->for($vpnProfileGroup)
            ->for($vpn34291)
            ->for($vpnEndpointVpn34291)
            ->create([
                'id' => $this->addCustomKey(VpnSession::class),
                'name' => '7. vpne_3429_1 > FastDesk Management - eCloud VPC',
                'remote_ip' => '45.131.138.9',
            ]);

        VpnSessionNetwork::factory()
            ->for($vpnSession)
            ->create([
                'id' => $this->addCustomKey(VpnSessionNetwork::class),
                'type' => 'local',
                'ip_address' => '10.10.4.0/24',
            ]);

        foreach (explode(',', '172.31.134.0/25,172.31.134.128/25,172.31.135.128/25,172.31.139.4/32') as $remoteNetwork) {
            VpnSessionNetwork::factory()
                ->for($vpnSession)
                ->create([
                    'id' => $this->addCustomKey(VpnSessionNetwork::class),
                    'type' => 'remote',
                    'ip_address' => $remoteNetwork,
                ]);
        }
        // <-- 7. vpne_3429_1 > FastDesk Management - eCloud VPC End -->

        // <-- Fastdesk - UKFast API (fw-23) Start -->
        $vpnSession = VpnSession::factory()
            ->for($vpnProfileGroup)
            ->for($fastDeskMgmt)
            ->for($vpnEndpointFastDeskMgmtOld)
            ->create([
                'id' => $this->addCustomKey(VpnSession::class),
                'name' => 'Fastdesk - UKFast API (fw-23)',
                'remote_ip' => '185.234.39.6',
            ]);

        VpnSessionNetwork::factory()
            ->for($vpnSession)
            ->create([
                'id' => $this->addCustomKey(VpnSessionNetwork::class),
                'type' => 'local',
                'ip_address' => '172.31.134.0/25',
            ]);

        VpnSessionNetwork::factory()
            ->for($vpnSession)
            ->create([
                'id' => $this->addCustomKey(VpnSessionNetwork::class),
                'type' => 'remote',
                'ip_address' => '172.31.196.0/24',
            ]);
        // <-- Fastdesk - UKFast API (fw-23) End -->

        // <-- FastDesk Old - SID 106821 Start -->
        $vpnSession = VpnSession::factory()
            ->for($vpnProfileGroup)
            ->for($fastDeskMgmt)
            ->for($vpnEndpointFastDeskMgmtOld)
            ->create([
                'id' => $this->addCustomKey(VpnSession::class),
                'name' => 'FastDesk Old - SID 106821',
                'remote_ip' => '46.37.181.196',
            ]);

        foreach (explode(',', '172.31.134.0/25,172.31.134.128/25') as $localNetwork) {
            VpnSessionNetwork::factory()
                ->for($vpnSession)
                ->create([
                    'id' => $this->addCustomKey(VpnSessionNetwork::class),
                    'type' => 'local',
                    'ip_address' => $localNetwork,
                ]);
        }

        foreach (explode(',', '172.27.125.128/25,172.26.232.0/21') as $remoteNetwork) {
            VpnSessionNetwork::factory()
                ->for($vpnSession)
                ->create([
                    'id' => $this->addCustomKey(VpnSessionNetwork::class),
                    'type' => 'remote',
                    'ip_address' => $remoteNetwork,
                ]);
        }
        // <-- FastDesk Old - SID 106821 End -->

        // <-- FastDesk Old - SID 86909 Start -->
        $vpnSession = VpnSession::factory()
            ->for($vpnProfileGroup)
            ->for($fastDeskMgmt)
            ->for($vpnEndpointFastDeskMgmtOld)
            ->create([
                'id' => $this->addCustomKey(VpnSession::class),
                'name' => 'FastDesk Old - SID 86909',
                'remote_ip' => '46.37.181.198',
            ]);

        VpnSessionNetwork::factory()
            ->for($vpnSession)
            ->create([
                'id' => $this->addCustomKey(VpnSessionNetwork::class),
                'type' => 'local',
                'ip_address' => '172.31.134.0/25',
            ]);

        VpnSessionNetwork::factory()
            ->for($vpnSession)
            ->create([
                'id' => $this->addCustomKey(VpnSessionNetwork::class),
                'type' => 'remote',
                'ip_address' => '192.168.68.0/25',
            ]);
        // <-- FastDesk Old - SID 86909 End -->
    }

    public function addCustomKey(string $className): string
    {
        $model = new $className;
        $suffix = App::environment() === 'local' ? '-dev' : '';

        try {
            do {
                $modelId = $model->keyPrefix . '-' . bin2hex(random_bytes(4)) . $suffix;
            } while ($model->withTrashed()->find($modelId));
        } catch (\Exception $exception) {
            Log::error('Failed to set Custom Key on ' . get_class($model), [
                $exception,
            ]);
            throw $exception;
        }

        return $modelId;
    }
}
