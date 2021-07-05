<?php

namespace Database\Seeders;

use App\Models\V2\AvailabilityZone;
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
        factory(AvailabilityZone::class)->create([
            'id' => 'az-aaaaaaaa',
            'code' => 'MAN1',
            'name' => 'Dev Availability Zone',
            'datacentre_site_id' => 8,
            'region_id' => 'reg-aaaaaaaa',
            'nsx_manager_endpoint' => 'https://185.197.63.88/',
            'nsx_edge_cluster_id' => '8bc61267-583e-4988-b5d9-16b46f7fe900',
            'san_name' => 'MCS-E-G0-3PAR-01',
            'ucs_compute_name' => 'GC-UCS-FI2-DEV-A',
            'is_public' => true,
        ]);

        // TODO az credentials
    }
}
