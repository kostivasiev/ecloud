<?php

namespace Database\Seeders\ResourceTiers;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\HostSpec;
use Illuminate\Database\Seeder;

class StandardCpuHostSpecSeeder extends Seeder
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
                'id' => 'hs-standard-cpu',
                'name' => 'Standard CPU Host Spec',
                'ucs_specification_name' => 'DUAL-E5-2620--32GB',
            ]);

        $availabilityZone->hostSpecs()->sync($hostSpec);
    }
}
