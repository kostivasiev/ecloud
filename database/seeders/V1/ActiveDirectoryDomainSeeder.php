<?php

namespace Database\Seeders\V1;

use App\Models\V1\ActiveDirectoryDomain;
use Illuminate\Database\Seeder;

class ActiveDirectoryDomainSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        ActiveDirectoryDomain::factory(5)->create();
    }
}
