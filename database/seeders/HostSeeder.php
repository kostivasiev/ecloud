<?php

namespace Database\Seeders;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Host;
use App\Models\V2\HostGroup;
use App\Models\V2\HostSpec;
use App\Models\V2\ResourceTier;
use App\Models\V2\Vpc;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class HostSeeder extends Seeder
{
    use WithoutModelEvents;

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
                'id' => 'hs-aaaaaaaa',
                'ucs_specification_name' => 'DUAL-4208--32GB',
            ]);
        $availabilityZone->hostSpecs()->sync($hostSpec);

        $hostGroup = HostGroup::factory()
            ->for(Vpc::find('vpc-aaaaaaaa'))
            ->for($availabilityZone)
            ->for($hostSpec)
            ->create([
                'id' => 'hg-aaaaaaaa',
            ]);

        Host::factory()
            ->for($hostGroup)
            ->create([
                'id' => 'h-aaaaaaaa',
                'name' => 'Test Host',
                'mac_address' => '00:00:5e:00:53:af',
            ]);

        $resourceTier = ResourceTier::find('rt-123456');
        $resourceTier->hostGroups()->attach($hostGroup);
    }
}
