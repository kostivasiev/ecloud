<?php

namespace Database\Seeders\V1;

use App\Models\V1\SolutionSite;
use Illuminate\Database\Seeder;

class SolutionSiteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        SolutionSite::factory()->create();
    }
}
