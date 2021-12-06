<?php

namespace Database\Seeders;

use App\Models\V2\Script;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Seeder;

class ScriptSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Script::factory()
            ->count(2)
            ->state(new Sequence(
                ['id' => 'scr-test-1', 'name' => 'Script 1', 'sequence' => 1],
                ['id' => 'scr-test-2', 'name' => 'Script 2', 'sequence' => 2],
                ['id' => 'scr-test-3', 'name' => 'Script 3', 'sequence' => 3],
            ))
            ->create([
                'software_id' => 'soft-test',
            ]);
    }
}
