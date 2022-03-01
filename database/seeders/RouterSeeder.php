<?php

namespace Database\Seeders;

use App\Models\V2\Router;
use Illuminate\Database\Seeder;

class RouterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        factory(Router::class)->create([
            'id' => 'rtr-aaaaaaaa',
            'vpc_id' => 'vpc-aaaaaaaa',
            'availability_zone_id' => 'az-aaaaaaaa',
            'router_throughput_id' => 'rtp-aaaaaaaa',
        ]);
    }
}
