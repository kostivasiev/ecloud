<?php

namespace Database\Seeders\ResourceTiers;

use App\Models\V2\HostGroup;
use Illuminate\Database\Seeder;

class HighCpuHostGroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        HostGroup::factory()->create([
            'id' => 'hg-f9660e12', // ID to match G0 cluster / host_group_id
            'name' => 'High CPU Host Group',
            'availability_zone_id' => 'az-aaaaaaaa',
            'host_spec_id' => 'hs-high-cpu',
        ]);
    }
}
