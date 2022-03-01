<?php

namespace Database\Seeders;

use App\Models\V2\Script;
use App\Models\V2\Software;
use Database\Seeders\Software\McafeeLinuxSoftwareSeeder;
use Database\Seeders\Software\McafeeWindowsSoftwareSeeder;
use Illuminate\Database\Eloquent\Factories\Sequence;
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
        /**
         * Default software for testing
         */
        Software::factory()->create(
            [
                'id' => 'soft-aaaaaaaa',
                'name' => 'Test Software',
                'license' => 'test software',
            ]
        );

        Script::factory()
            ->count(2)
            ->state(new Sequence(
                ['id' => 'scr-test-1', 'name' => 'Script 1', 'sequence' => 1],
                ['id' => 'scr-test-2', 'name' => 'Script 2', 'sequence' => 2],
            ))
            ->create([
                'software_id' => 'soft-aaaaaaaa',
            ]);

        /**
         * Other Software
         */
        $this->call(McafeeLinuxSoftwareSeeder::class);
        $this->call(McafeeWindowsSoftwareSeeder::class);
    }
}
