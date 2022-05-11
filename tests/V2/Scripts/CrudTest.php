<?php

namespace Tests\V2\Scripts;

use App\Models\V2\Software;
use Database\Seeders\SoftwareSeeder;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class CrudTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
        (new SoftwareSeeder())->run();
    }

    public function testIndex()
    {
        // 'public' visibility of related software displays to non admin
        $this->get('/v2/scripts')
            ->assertJsonFragment([
                'id' => 'scr-test-1',
                'name' => 'Script 1',
                'software_id' => 'soft-aaaaaaaa',
                'sequence' => 1,
                'script' => 'exit 0',
            ])
            ->assertStatus(200);

        $software = Software::first();
        $software->setAttribute('visibility', Software::VISIBILITY_PRIVATE)->save();

        // 'private' visibility of related software does not display to non admin
        $this->get('/v2/scripts')
            ->assertJsonMissing([
                'id' => 'scr-test-1'
            ])
            ->assertStatus(200);

        // 'private' visibility of related software displays to admin
        $this->be((new Consumer(0, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));
        $this->get('/v2/scripts')
            ->assertJsonFragment([
                'id' => 'scr-test-1',
                'name' => 'Script 1',
                'software_id' => 'soft-aaaaaaaa',
                'sequence' => 1,
                'script' => 'exit 0',
            ])
            ->assertStatus(200);
    }

    public function testShow()
    {
        // 'public' visibility of related software displays to non admin
        $this->get('/v2/scripts/scr-test-1')
            ->assertJsonFragment([
                'id' => 'scr-test-1',
                'name' => 'Script 1',
                'software_id' => 'soft-aaaaaaaa',
                'sequence' => 1,
                'script' => 'exit 0',
            ])
            ->assertStatus(200);

        $software = Software::first();
        $software->setAttribute('visibility', Software::VISIBILITY_PRIVATE)->save();

        // 'private' visibility of related software does not display to non admin
        $this->get('/v2/scripts/scr-test-1')->assertStatus(404);

        // 'private' visibility of related software displays to admin
        $this->be((new Consumer(0, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));
        $this->get('/v2/scripts/scr-test-1')
            ->assertJsonFragment([
                'id' => 'scr-test-1',
                'name' => 'Script 1',
                'software_id' => 'soft-aaaaaaaa',
                'sequence' => 1,
                'script' => 'exit 0',
            ])
            ->assertStatus(200);
    }

    public function testStore()
    {
        $data = [
            'name' => 'Script Test',
            'software_id' => 'soft-aaaaaaaa',
            'script' => 'exit 0'
        ];

        // Not admin fails
        $this->post('/v2/scripts', $data)->assertStatus(401);

        $this->be((new Consumer(0, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));

        // Sequence number already used fails
        $this->post('/v2/scripts', array_merge($data, ['sequence' => 1]))->assertStatus(422);

        $data = array_merge($data, ['sequence' => 4]);
        $this->post('/v2/scripts', $data)
            ->assertStatus(201);
        $this->assertDatabaseHas('scripts', $data, 'ecloud');
    }

    public function testUpdate()
    {
        $data = [
            'name' => 'Test - UPDATED',
            'sequence' => 99,
            'script' => 'exit 1',
        ];

        // Not admin fails
        $this->patch('/v2/scripts/scr-test-1', $data)->assertStatus(401);

        $this->be((new Consumer(0, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));
        $this->patch('/v2/scripts/scr-test-1', $data)
            ->assertStatus(200);
        $this->assertDatabaseHas('scripts', $data, 'ecloud');
    }

    public function testDestroy()
    {
        // Not admin fails
        $this->delete('/v2/scripts/scr-test-1')->assertStatus(401);

        $this->be((new Consumer(0, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));
        $this->delete('/v2/scripts/scr-test-1')
            ->assertStatus(204);
        $this->assertDatabaseMissing(
            'scripts',
            [
                'id' => 'scr-test-1',
                'deleted_at' => null,
            ],
            'ecloud'
        );
    }
}
