<?php

namespace Database\Seeders;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\AvailabilityZoneCapacity;
use App\Models\V2\Credential;
use Illuminate\Database\Seeder;

class AvailabilityZoneSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        AvailabilityZone::factory()->create([
            'id' => 'az-aaaaaaaa',
            'code' => 'MAN1',
            'name' => 'Dev Availability Zone',
            'datacentre_site_id' => 8,
            'region_id' => 'reg-aaaaaaaa',
            'san_name' => 'MCS-E-G0-3PAR-01',
            'ucs_compute_name' => 'GC-UCS-FI2-DEV-A',
            'is_public' => true,
        ]);

        /**
         * Credentials
         */
        Credential::factory()->create([
            'id' => 'cred-kingpin',
            'name' => 'Kingpin (G0)',
            'resource_id'=> 'az-aaaaaaaa',
            'host'=> 'https://mgmt-20.ecloud-service.ukfast.co.uk',
            'username'=> 'kingpinapi',
            'password'=> env('KINGPIN_PASSWORD'),
            'port'=> '8443',
            'is_hidden'=> false,
        ]);

        Credential::factory()->create([
            'id' => 'cred-nsx',
            'name' => 'NSX',
            'resource_id'=> 'az-aaaaaaaa',
            'host'=> 'https://185.197.63.88',
            'username'=> 'ecloud.api@ecloudgov.dev',
            'password'=> env('NSX_PASSWORD'),
            'port'=> null,
            'is_hidden'=> false,
        ]);

        Credential::factory()->create([
            'id' => 'cred-conjurer',
            'name' => 'Conjurer API',
            'resource_id'=> 'az-aaaaaaaa',
            'host'=> 'https://compute-20.ecloud-service.ukfast.co.uk',
            'username'=> 'conjurerapi',
            'password'=> env('CONJURER_PASSWORD'),
            'port'=> 8444,
            'is_hidden'=> false,
        ]);

        Credential::factory()->create([
            'id' => 'cred-3par',
            'name' => '3PAR',
            'resource_id'=> 'az-aaaaaaaa',
            'host'=> null,
            'username'=> 'apiuser',
            'password'=> env('3PAR_PASSWORD'),
            'port'=> null,
            'is_hidden'=> false,
        ]);

        Credential::factory()->create([
            'id' => 'cred-artisan',
            'name' => 'Artisan API',
            'resource_id'=> 'az-aaaaaaaa',
            'host'=> 'https://storage-20.ecloud-service.ukfast.co.uk',
            'username'=> 'artisanapi',
            'password'=> env('ARTISAN_PASSWORD'),
            'port'=> 8446,
            'is_hidden'=> false,
        ]);

        Credential::factory()->create([
            'id' => 'cred-ucs',
            'name' => 'UCS API',
            'resource_id'=> 'az-aaaaaaaa',
            'host'=> null,
            'username'=> 'ucs-api',
            'password'=> env('UCS_PASSWORD'),
            'port'=> null,
            'is_hidden'=> false,
        ]);

        Credential::factory()->create([
            'id' => 'cred-envoy',
            'name' => 'Envoy',
            'resource_id'=> 'az-aaaaaaaa',
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
            'id' => 'azc-aaaaaaaa',
            'availability_zone_id' => 'az-aaaaaaaa',
            'type' => 'floating_ip',
            'alert_warning' => 60,
            'alert_critical' => 80,
            'max' => 95,
        ]);
    }
}
