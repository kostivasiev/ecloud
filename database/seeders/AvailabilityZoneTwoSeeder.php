<?php

namespace Database\Seeders;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\AvailabilityZoneCapacity;
use App\Models\V2\Credential;
use App\Models\V2\Network;
use App\Models\V2\Router;
use App\Models\V2\RouterThroughput;
use Illuminate\Database\Seeder;

class AvailabilityZoneTwoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        RouterThroughput::factory()->create([
            'id' => 'rtp-bbbbbbbb',
            'availability_zone_id' => 'az-bbbbbbbb',
            'committed_bandwidth' => config('router.throughput.default.bandwidth')
        ]);

        Network::factory()->create([
            'id' => 'net-bbbbbbbb',
            'name' => 'Dev Network 2',
            'subnet' => '10.0.0.0/24',
            'router_id' => 'rtr-bbbbbbbb'
        ]);

        Router::factory()->create([
            'id' => 'rtr-bbbbbbbb',
            'vpc_id' => 'vpc-aaaaaaaa',
            'availability_zone_id' => 'az-bbbbbbbb',
            'router_throughput_id' => 'rtp-bbbbbbbb',
        ]);

        AvailabilityZone::factory()->create([
            'id' => 'az-bbbbbbbb',
            'code' => 'MAN2',
            'name' => 'Dev Availability Zone 2',
            'datacentre_site_id' => 8,
            'region_id' => 'reg-aaaaaaaa',
            'san_name' => 'MCS-E-G0-3PAR-02',
            'ucs_compute_name' => 'GC-UCS-FI2-DEV-B',
            'is_public' => true,
            'resource_tier_id' => 'rt-aaaaaaaa',
        ]);

        /**
         * Credentials
         */
        Credential::factory()->create([
            'id' => 'cred-kingpin-2',
            'name' => 'Kingpin (G0)',
            'resource_id'=> 'az-bbbbbbbb',
            'host'=> 'https://mgmt-20.ecloud-service.ukfast.co.uk',
            'username'=> 'kingpinapi',
            'password'=> env('KINGPIN_PASSWORD'),
            'port'=> '8443',
            'is_hidden'=> false,
        ]);

        Credential::factory()->create([
            'id' => 'cred-nsx-2',
            'name' => 'NSX',
            'resource_id'=> 'az-bbbbbbbb',
            'host'=> 'https://185.197.63.88',
            'username'=> 'ecloud.api@ecloudgov.dev',
            'password'=> env('NSX_PASSWORD'),
            'port'=> null,
            'is_hidden'=> false,
        ]);

        Credential::factory()->create([
            'id' => 'cred-conjurer-2',
            'name' => 'Conjurer API',
            'resource_id'=> 'az-bbbbbbbb',
            'host'=> 'https://compute-20.ecloud-service.ukfast.co.uk',
            'username'=> 'conjurerapi',
            'password'=> env('CONJURER_PASSWORD'),
            'port'=> 8444,
            'is_hidden'=> false,
        ]);

        Credential::factory()->create([
            'id' => 'cred-3par-2',
            'name' => '3PAR',
            'resource_id'=> 'az-bbbbbbbb',
            'host'=> null,
            'username'=> 'apiuser',
            'password'=> env('3PAR_PASSWORD'),
            'port'=> null,
            'is_hidden'=> false,
        ]);

        Credential::factory()->create([
            'id' => 'cred-artisan-2',
            'name' => 'Artisan API',
            'resource_id'=> 'az-bbbbbbbb',
            'host'=> 'https://storage-20.ecloud-service.ukfast.co.uk',
            'username'=> 'artisanapi',
            'password'=> env('ARTISAN_PASSWORD'),
            'port'=> 8446,
            'is_hidden'=> false,
        ]);

        Credential::factory()->create([
            'id' => 'cred-ucs-2',
            'name' => 'UCS API',
            'resource_id'=> 'az-bbbbbbbb',
            'host'=> null,
            'username'=> 'ucs-api',
            'password'=> env('UCS_PASSWORD'),
            'port'=> null,
            'is_hidden'=> false,
        ]);

        Credential::factory()->create([
            'id' => 'cred-envoy-2',
            'name' => 'Envoy',
            'resource_id'=> 'az-bbbbbbbb',
            'host'=> 'https://185.197.63.87',
            'username'=> 'envoyapi',
            'password'=> env('ENVOY_PASSWORD'),
            'port'=> 9443,
            'is_hidden'=> false,
        ]);

        /**
         * Capacity Alerting
         */
        AvailabilityZoneCapacity::factory()->create([
            'id' => 'azc-bbbbbbbb',
            'availability_zone_id' => 'az-bbbbbbbb',
            'type' => 'floating_ip',
            'alert_warning' => 60,
            'alert_critical' => 80,
            'max' => 95,
        ]);
    }
}
