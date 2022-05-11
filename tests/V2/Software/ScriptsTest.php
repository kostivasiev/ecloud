<?php

namespace Tests\V2\Software;

use Database\Seeders\SoftwareSeeder;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class ScriptsTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
        (new SoftwareSeeder())->run();
    }

    public function testShow()
    {
        $this->get('/v2/software/soft-aaaaaaaa/scripts')
            ->assertJsonFragment([
                'id' => 'scr-test-1',
                'name' => 'Script 1',
                'software_id' => 'soft-aaaaaaaa',
                'sequence' => 1,
                'script' => 'exit 0',
            ])
            ->assertStatus(200);
    }
}
