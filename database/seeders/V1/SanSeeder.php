<?php

namespace Database\Seeders\V1;

use App\Models\V1\Pod;
use App\Models\V1\San;
use App\Models\V1\Solution;
use App\Models\V1\Storage;
use Illuminate\Database\Seeder;

class SanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $pod = Pod::factory()->create();
        $san = San::find(1);
        if (!$san) {
            $san = San::factory()->create(); //server_id
        }
        Storage::factory()->create([
            'server_id' => $san->getKey(),
            'ucs_datacentre_id' => $pod->getKey(),
        ]);
        Solution::factory()->create([
            'ucs_reseller_reseller_id' => 1,
            'ucs_reseller_datacentre_id' => $pod->getAttribute('ucs_datacentre_id'),
        ]);
    }
}