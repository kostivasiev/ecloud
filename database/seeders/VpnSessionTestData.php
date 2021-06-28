<?php

namespace Database\Seeders;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Router;
use App\Models\V2\VpnEndpoint;
use App\Models\V2\VpnProfileGroup;
use App\Models\V2\VpnService;
use App\Models\V2\VpnSession;
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
                'ike_profile_id' => 'ike-abc123xyz',
                'ipsec_profile_id' => 'ipsec-abc123xyz',
                'dpd_profile_id' => 'dpd-abc123xyz',
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
//                'vpn_service_id' => 'vpn-aaaaaaaa',
                'fip_id' => 'fip-aaaaaaaa',
            ]);
        VpnSession::on('ecloud')
            ->create([
                'id' => 'vpns-aaaaaaaa',
                'name' => 'Test VPN Session',
                'vpn_profile_group_id' => 'vpnpg-aaaaaaaa',
                'remote_ip' => '211.12.13.1',
                'remote_networks' => '172.12.23.11/32',
                'local_networks' => '172.11.11.11/32,176.18.22.11/24',
            ]);
    }
}
