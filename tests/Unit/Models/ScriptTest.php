<?php

namespace Tests\Unit\Models;

use App\Models\V2\Script;
use Database\Seeders\SoftwareSeeder;
use Tests\TestCase;

class ScriptTest extends TestCase
{
    protected $task;
    
    public function setUp(): void
    {
        parent::setUp();
        (new SoftwareSeeder())->run();
    }

    public function testAssignsSequence()
    {
        Script::truncate();

        $script = Script::factory()->create([
            'software_id' => 'soft-aaaaaaaa',
        ]);

        $this->assertEquals(1, $script->sequence);

        $script = Script::factory()->create([
            'software_id' => 'soft-aaaaaaaa',
        ]);

        $this->assertEquals(2, $script->sequence);

        $script->delete();

        $script = Script::factory()->create([
            'software_id' => 'soft-aaaaaaaa',
        ]);

        $this->assertEquals(2, $script->sequence);
    }
}
