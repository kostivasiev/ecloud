<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * php artisan db:seed --database=ecloud
     *
     * @return void
     */
    public function run()
    {
        $this->call(RegionSeeder::class);
        $this->call(AvailabilityZoneSeeder::class);
    }
}

