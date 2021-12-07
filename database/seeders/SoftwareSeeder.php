<?php

namespace Database\Seeders;

use App\Models\V2\Software;
use Illuminate\Database\Seeder;

class SoftwareSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Software::factory()->create(
            [
                'id' => 'soft-aaaaaaaa',
                'name' => 'Test Software'
            ]
        );

        $this->call(ScriptSeeder::class);
    }
}
