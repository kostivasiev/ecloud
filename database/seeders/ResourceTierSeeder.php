<?php

namespace Database\Seeders;

use App\Models\V2\ResourceTier;
use Illuminate\Database\Seeder;
use Illuminate\Foundation\Testing\WithFaker;

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
            'id' => 'rt-123456',
            'name' => 'Dev Tier',
            'availability_zone_id' => 'az-aaaaaaaa'
        ]);
    }
}
