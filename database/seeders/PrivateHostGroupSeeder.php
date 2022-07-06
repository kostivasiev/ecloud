<?php

namespace Database\Seeders;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Host;
use App\Models\V2\HostGroup;
use App\Models\V2\HostSpec;
use App\Models\V2\Vpc;
use Illuminate\Database\Seeder;

class PrivateHostGroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        HostGroup::withoutEvents(function () {
            $hostGroup = HostGroup::factory()
                ->for(Vpc::find('vpc-aaaaaaaa'))
                ->for(AvailabilityZone::find('az-aaaaaaaa'))
                ->for(HostSpec::find('hs-aaaaaaaa'))
                ->create([
                    'id' => 'hg-aaaaaaaa',
                    'name' > 'Test Private Host Group'
                ]);
            Host::factory()
                ->for($hostGroup)
                ->create([
                    'id' => 'h-aaaaaaaa',
                    'name' => 'Test Host',
                    'mac_address' => '00:00:5e:00:53:af',
                ]);
        });
    }
}
