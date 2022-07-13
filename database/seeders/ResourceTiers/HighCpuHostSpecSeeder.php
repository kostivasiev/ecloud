<?php

namespace Database\Seeders\ResourceTiers;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\HostSpec;
use Illuminate\Database\Seeder;

class HighCpuHostSpecSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $availabilityZone = AvailabilityZone::find('az-aaaaaaaa');

        $hostSpec = HostSpec::factory()
            ->create([
                'id' => 'hs-high-cpu',
                'name' => 'DUAL-4208--32GB', //Standard CPU Host Spec
                'ucs_specification_name' => 'DUAL-4208--32GB',
                'cpu_sockets' => 2,
                'cpu_type' => 'E5-2620 v1',
                'cpu_cores' => 6,
                'cpu_clock_speed' => 2000,
                'ram_capacity' => 32,
                'is_hidden' => true,
            ]);

        $availabilityZone->hostSpecs()->sync($hostSpec);
    }
}
