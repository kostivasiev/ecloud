<?php

namespace Database\Seeders;

use App\Models\V2\LoadBalancer;
use Database\Seeders\Images\LoadBalancerImageSeeder;
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

        factory(LoadBalancer::class)->create([
            'vpc_id' => 'vpc-aaaaaaaa',
            'load_balancer_spec_id' => 'lbs-aaaaaaaa',
            'name' => 'Dev LBC',
            'availability_zone_id' => 'az-aaaaaaaa'
        ]);

        $this->call(LoadBalancerImageSeeder::class);
    }
}
