<?php

namespace Tests\V2\Software;

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
        // Assert public visibility is returned for non admin
        $this->get('/v2/software')
            ->assertJsonFragment([
                'id' => 'soft-aaaaaaaa',
                'name' => 'Test Software',
                'platform' => 'Linux',
            ])
            ->assertStatus(200);

        $software = Software::find('soft-aaaaaaaa');
        $software->setAttribute('visibility', Software::VISIBILITY_PRIVATE)->save();

        // Assert private visibility is not returned to non admin
        $this->get('/v2/software')
            ->assertJsonMissing([
                'id' => 'soft-aaaaaaaa'
            ])
            ->assertStatus(200);

        // Assert private visibility is returned for admin
        $this->be((new Consumer(0, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));
        $this->get('/v2/software/soft-aaaaaaaa')
            ->assertJsonFragment([
                'id' => 'soft-aaaaaaaa',
                'name' => 'Test Software',
                'platform' => 'Linux',
                'visibility' => 'private',
            ])
            ->assertStatus(200);
    }

    public function testShow()
    {
        // Assert public visibility is returned for non admin
        $this->get('/v2/software/soft-aaaaaaaa')
            ->assertJsonFragment([
                'id' => 'soft-aaaaaaaa',
                'name' => 'Test Software',
                'platform' => 'Linux',
            ])
            ->assertStatus(200);

        // Assert private visibility is not returned to non admin
        $software = Software::first();
        $software->setAttribute('visibility', Software::VISIBILITY_PRIVATE)->save();
        $this->get('/v2/software/soft-aaaaaaaa')->assertStatus(404);

        // Assert private visibility is returned for admin
        $this->be((new Consumer(0, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));
        $this->get('/v2/software/soft-aaaaaaaa')->assertStatus(200);
    }

    public function testStore()
    {
        $data = [
            'name' => 'Test',
            'platform' => 'Linux',
            'visibility' => 'public',
        ];

        // Not admin fails
        $this->post('/v2/software', $data)->assertStatus(401);

        $this->be((new Consumer(0, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));
        $this->post('/v2/software', $data)->assertStatus(201);
        $this->assertDatabaseHas('software', $data, 'ecloud');
    }

    public function testUpdate()
    {
        $data = [
            'name' => 'Test - UPDATED',
            'platform' => 'Windows',
            'visibility' => 'private',
        ];

        // Not admin fails
        $this->patch('/v2/software/soft-aaaaaaaa', $data)->assertStatus(401);

        $this->be((new Consumer(0, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));
        $this->patch('/v2/software/soft-aaaaaaaa', $data)->assertStatus(200);
        $this->assertDatabaseHas('software', $data, 'ecloud');
    }

    public function testDestroy()
    {
        // Not admin fails
        $this->delete('/v2/software/soft-aaaaaaaa')->assertStatus(401);

        $this->be((new Consumer(0, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));
        $this->delete('/v2/software/soft-aaaaaaaa')
            ->assertStatus(204);
        $this->assertDatabaseMissing(
            'software',
            [
                'id' => 'soft-aaaaaaaa',
                'deleted_at' => null,
            ],
            'ecloud'
        );
    }
}
