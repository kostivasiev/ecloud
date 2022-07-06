<?php

namespace Database\Seeders;

use App\Models\V2\HostSpec;
use Illuminate\Database\Seeder;

class HostSpecSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        HostSpec::factory()
            ->create([
                'id' => 'hs-aaaaaaaa',
                'ucs_specification_name' => 'DUAL-4208--32GB',
            ]);
    }
}
