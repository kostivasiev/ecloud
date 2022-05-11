<?php

namespace Database\Seeders;

use App\Models\V2\RouterThroughput;
use Illuminate\Database\Seeder;

class RouterThroughputSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        RouterThroughput::factory()->create([
            'id' => 'rtp-aaaaaaaa',
            'availability_zone_id' => 'az-aaaaaaaa',
            'committed_bandwidth' => config('router.throughput.default.bandwidth')
        ]);

        RouterThroughput::factory()->create([
            'availability_zone_id' => 'az-aaaaaaaa',
            'committed_bandwidth' => config('router.throughput.admin_default.bandwidth')
        ]);
    }
}
