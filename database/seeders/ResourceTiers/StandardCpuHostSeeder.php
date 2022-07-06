<?php

namespace Database\Seeders\ResourceTiers;

use App\Models\V2\Host;
use Illuminate\Database\Seeder;

class StandardCpuHostSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // TODO: This is subject to change and will need to be updated
        Host::factory()->create([
            'id' => 'h-standard-cpu',
            'name' => 'Standard CPU Tier Host',
            'host_group_id' => 'hg-standard-cpu',
            'mac_address' => '00:00:5e:00:53:af',
        ]);
    }
}
