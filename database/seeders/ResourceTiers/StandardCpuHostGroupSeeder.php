<?php

namespace Database\Seeders\ResourceTiers;

use App\Models\V2\HostGroup;
use Illuminate\Database\Seeder;

class StandardCpuHostGroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        HostGroup::factory()->create([
            'id' => 'hg-standard-cpu',
            'name' => 'Standard CPU Host Group',
            'availability_zone_id' => 'az-aaaaaaaa',
            'host_spec_id' => 'hs-standard-cpu',
        ]);
    }
}
