<?php

namespace Database\Seeders\AffinityRules;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\HostGroup;
use App\Models\V2\HostSpec;
use App\Models\V2\Vpc;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;

class DockerSeeder extends Seeder
{
    public function run()
    {

        Model::withoutEvents(function () {
            $availabilityZone = AvailabilityZone::findOrFail('az-aaaaaaaa');
            $vpc = Vpc::findOrFail('vpc-aaaaaaaa');
            $hostSpec = HostSpec::factory()
                ->create([
                    'id' => 'hs-aaaaaaaa',
                    'ucs_specification_name' => 'test-host-spec',
                    'cpu_type' => 'E5-2643 v3',
                    'cpu_sockets' => 2,
                    'cpu_cores' => 6,
                    'cpu_clock_speed' => 4000,
                    'ram_capacity' => 64,
                    'name' => 'test-host-spec',
                ]);
            $hostSpec->availabilityZones()->save($availabilityZone);
            HostGroup::factory()
                ->for($vpc)
                ->for($availabilityZone)
                ->for($hostSpec)
                ->create([
                    'id' => '1001',
                    'name' => 'Affinity Rules Hostgroup',
                    'windows_enabled' => false,
                ]);
        });
    }
}