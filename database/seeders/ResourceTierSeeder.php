<?php

namespace Database\Seeders;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\ResourceTier;
use App\Models\V2\ResourceTierHostGroup;
use Database\Seeders\ResourceTiers\HighCpuHostGroupSeeder;
use Database\Seeders\ResourceTiers\HighCpuHostSeeder;
use Database\Seeders\ResourceTiers\HighCpuHostSpecSeeder;
use Database\Seeders\ResourceTiers\StandardCpuHostGroupSeeder;
use Database\Seeders\ResourceTiers\StandardCpuHostSeeder;
use Database\Seeders\ResourceTiers\StandardCpuHostSpecSeeder;
use Illuminate\Database\Seeder;

class ResourceTierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        ResourceTier::factory()->create([
            'id' => 'rt-aaaaaaaa',
            'name' => 'Standard CPU',
            'availability_zone_id' => 'az-aaaaaaaa'
        ]);

        // Set the default resource tier for the az
        AvailabilityZone::findOrFail('az-aaaaaaaa')->setAttribute('resource_tier_id', 'rt-aaaaaaaa')->saveQuietly();

        $this->call(StandardCpuHostSpecSeeder::class);
        $this->call(StandardCpuHostGroupSeeder::class);
        $this->call(StandardCpuHostSeeder::class);

        $this->call(HighCpuHostSpecSeeder::class);
        $this->call(HighCpuHostGroupSeeder::class);
        $this->call(HighCpuHostSeeder::class);

        ResourceTierHostGroup::factory()->create([
            'id' => 'rthg-standard-cpu',
            'resource_tier_id' => 'rt-aaaaaaaa',
            'host_group_id' => 'hg-99f9b758'
        ]);

        ResourceTier::factory()->create([
            'id' => 'rt-high-cpu',
            'name' => 'High CPU',
            'availability_zone_id' => 'az-aaaaaaaa',
        ]);

        ResourceTierHostGroup::factory()->create([
            'id' => 'rthg-high-cpu',
            'resource_tier_id' => 'rt-high-cpu',
            'host_group_id' => 'hg-f9660e12'
        ]);
    }
}
