<?php

namespace Database\Seeders\Tests;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Instance;
use App\Models\V2\Router;
use App\Models\V2\Volume;
use App\Models\V2\Vpc;
use App\Models\V2\VpnEndpoint;
use App\Models\V2\VpnService;
use App\Models\V2\VpnSession;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class VolumeAndVpnSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
            Volume::factory(3)
                ->for(Vpc::factory())
                ->for(AvailabilityZone::factory())
                ->create();

        for ($k=0; $k < 5; $k++) {
            $volume = Volume::factory()
                ->for(Vpc::factory())
                ->for(AvailabilityZone::factory())
                ->create();
            $instance = Instance::factory()->create();
            $instance->volumes()->save($volume);
        }

        for ($k=0; $k < 5; $k++) {
            VpnService::factory()
                ->for(
                    Router::factory()->for(
                        AvailabilityZone::factory()
                    )->for(Vpc::factory())
                )
                ->has(VpnEndpoint::factory())
                ->has(VpnSession::factory())
                ->create();
        }
    }
}
