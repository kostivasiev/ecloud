<?php

namespace Database\Seeders;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Instance;
use App\Models\V2\Vpc;
use Illuminate\Database\Seeder;

class InstanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Instance::factory()->create([
            'id' => 'i-aaaaaaaa',
            'name' => 'Test Instance',
            'vpc_id' => Vpc::first()->id,
            'availability_zone_id' => AvailabilityZone::first()->id,
        ]);
    }
}
