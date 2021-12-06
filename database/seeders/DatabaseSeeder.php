<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * php artisan db:seed
     *
     * @return void
     */
    public function run()
    {
        // API level resources
        $this->call(RegionSeeder::class);
        $this->call(AvailabilityZoneSeeder::class);
        $this->call(RouterThroughputSeeder::class);
        $this->call(ImageSeeder::class);
        $this->call(LoadBalancerSeeder::class);
        $this->call(SoftwareSeeder::class);

        // Create some dev resources for test reseller 7052
        $this->call(SshKeyPairSeeder::class);
        $this->call(VpcSeeder::class);
        $this->call(RouterSeeder::class);
        $this->call(NetworkSeeder::class);
//        $this->call(BillingMetricSeeder::class);
    }
}

