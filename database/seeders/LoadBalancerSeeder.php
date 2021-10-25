<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class LoadBalancerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->call(LoadBalancerSpecificationSeeder::class);
        $this->call(LoadBalancerImageSeeder::class);
    }
}
