<?php

namespace Database\Seeders;

use App\Models\V2\Dhcp;
use App\Models\V2\LoadBalancer;
use App\Models\V2\Vpc;
use Illuminate\Database\Seeder;

class VpcSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        factory(Vpc::class)->create([
            'id' => 'vpc-aaaaaaaa',
            'name' => 'Dev VPC',
            'region_id' => 'reg-aaaaaaaa',
            'reseller_id' => 7052,
            'console_enabled' => true,
            'advanced_networking' => true
        ]);

        // Todo: convert this to use a factory when DHCP resource has been updated to use syncSave()
        app()->make(Dhcp::class)->fill([
            'id' => 'dhcp-aaaaaaaa',
            'name' => 'dhcp-aaaaaaaa',
            'vpc_id' => 'vpc-aaaaaaaa',
            'availability_zone_id' => 'az-aaaaaaaa'
        ])->saveQuietly();
    }
}
