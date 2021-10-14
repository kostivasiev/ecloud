<?php

namespace Database\Seeders;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Router;
use App\Models\V2\VpnEndpoint;
use App\Models\V2\VpnProfileGroup;
use App\Models\V2\VpnService;
use App\Models\V2\VpnSession;
use App\Models\V2\VpnSessionNetwork;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VpnSessionTestData extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        VpnProfileGroup::on('ecloud')
            ->create([
                'id' => 'vpnpg-aaaaaaaa',
                'name' => 'Test Profile Group',
                'description' => 'Test Profile Group Description',
                'ike_profile_id' => 'nsx-default-l3vpn-ike-profile',
                'ipsec_profile_id' => 'nsx-default-l3vpn-tunnel-profile',
                'availability_zone_id' => 'az-aaaaaaaa',
            ]);
        VpnService::on('ecloud')
            ->create([
                'id' => 'vpn-aaaaaaaa',
                'router_id' => 'rtr-aaaaaaaa',
                'name' => 'Test Router',
            ]);
        VpnEndpoint::on('ecloud')
            ->create([
                'id' => 'vpne-aaaaaaaa',
                'name' => 'Test VPN Endpoint',
                'vpn_service_id' => 'vpn-aaaaaaaa',
            ]);
        VpnSession::on('ecloud')
            ->create([
                'id' => 'vpns-aaaaaaaa',
                'name' => 'Test VPN Session',
                'vpn_profile_group_id' => 'vpnpg-aaaaaaaa',
                'remote_ip' => '211.12.13.1',
            ]);
        VpnSessionNetwork::on('ecloud')
            ->create([
                'id' => 'vpnsn-aaaaaaaa',
                'vpn_session_id' => 'vpns-aaaaaaaa',
                'type' => 'local',
                'ip_address' => '172.11.11.11/32',
            ]);
        VpnSessionNetwork::on('ecloud')
            ->create([
                'id' => 'vpnsn-bbbbbbbb',
                'vpn_session_id' => 'vpns-aaaaaaaa',
                'type' => 'local',
                'ip_address' => '176.18.22.11/24',
            ]);
        VpnSessionNetwork::on('ecloud')
            ->create([
                'id' => 'vpnsn-cccccccc',
                'vpn_session_id' => 'vpns-aaaaaaaa',
                'type' => 'remote',
                'ip_address' => '172.12.23.11/32',
            ]);
    }
}
