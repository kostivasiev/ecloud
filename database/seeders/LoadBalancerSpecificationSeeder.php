<?php

namespace Database\Seeders;

use App\Models\V2\LoadBalancerSpecification;
use Illuminate\Database\Seeder;

class LoadBalancerSpecificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        factory(LoadBalancerSpecification::class)->create([
                'name' => 'small',
                'node_count' => 1,
                'cpu' => 1,
                'ram' => 2,
                'hdd' => 20,
                'iops' => 300,
                'image_id' => 'image-id-1',
        ]);

        factory(LoadBalancerSpecification::class)->create([
                'id' => 'lbs-aaaaaaaa', // dev_load_balancer_spec_id
                'name' => 'medium',
                'node_count' => 2,
                'cpu' => 2,
                'ram' => 4,
                'hdd' => 20,
                'iops' => 300,
                'image_id' => 'image-id-2',
        ]);

        factory(LoadBalancerSpecification::class)->create([
                'name' => 'large',
                'node_count' => 2,
                'cpu' => 4,
                'ram' => 8,
                'hdd' => 20,
                'iops' => 300,
                'image_id' => 'image-id-3',
        ]);
    }
}
