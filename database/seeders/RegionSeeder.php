<?php

namespace Database\Seeders;

use App\Models\V2\Region;
use Illuminate\Database\Seeder;

class RegionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        factory(Region::class)->create([
            'id' => 'reg-aaaaaaaa',
            'name' => 'Dev Region',
            'is_public' => true,
        ]);
    }
}
