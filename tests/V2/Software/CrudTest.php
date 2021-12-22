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
            ->seeJson([
                'id' => 'soft-aaaaaaaa',
                'name' => 'Test Software',
                'platform' => 'Linux',
            ])
            ->assertResponseStatus(200);

        $software = Software::find('soft-aaaaaaaa');
        $software->setAttribute('visibility', Software::VISIBILITY_PRIVATE)->save();

        // Assert private visibility is not returned to non admin
        $this->get('/v2/software')
            ->dontSeeJson([
                'id' => 'soft-aaaaaaaa'
            ])
            ->assertResponseStatus(200);

        // Assert private visibility is returned for admin
        $this->be((new Consumer(0, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));
        $this->get('/v2/software/soft-aaaaaaaa')
            ->seeJson([
                'id' => 'soft-aaaaaaaa',
                'name' => 'Test Software',
                'platform' => 'Linux',
                'visibility' => 'private',
            ])
            ->assertResponseStatus(200);
    }

    public function testShow()
    {
        // Assert public visibility is returned for non admin
        $this->get('/v2/software/soft-aaaaaaaa')
            ->seeJson([
                'id' => 'soft-aaaaaaaa',
                'name' => 'Test Software',
                'platform' => 'Linux',
            ])
            ->assertResponseStatus(200);

        // Assert private visibility is not returned to non admin
        $software = Software::first();
        $software->setAttribute('visibility', Software::VISIBILITY_PRIVATE)->save();
        $this->get('/v2/software/soft-aaaaaaaa')->assertResponseStatus(404);

        // Assert private visibility is returned for admin
        $this->be((new Consumer(0, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));
        $this->get('/v2/software/soft-aaaaaaaa')->assertResponseStatus(200);
    }

    public function testStore()
    {
        $data = [
            'name' => 'Test',
            'platform' => 'Linux',
            'visibility' => 'public',
        ];

        // Not admin fails
        $this->post('/v2/software', $data)->assertResponseStatus(401);

        $this->be((new Consumer(0, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));
        $this->post('/v2/software', $data)
            ->seeInDatabase('software', $data, 'ecloud')
            ->assertResponseStatus(201);
    }

    public function testUpdate()
    {
        $data = [
            'name' => 'Test - UPDATED',
            'platform' => 'Windows',
            'visibility' => 'private',
        ];

        // Not admin fails
        $this->patch('/v2/software/soft-aaaaaaaa', $data)->assertResponseStatus(401);

        $this->be((new Consumer(0, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));
        $this->patch('/v2/software/soft-aaaaaaaa', $data)
            ->seeInDatabase('software', $data, 'ecloud')
            ->assertResponseStatus(200);
    }

    public function testDestroy()
    {
        // Not admin fails
        $this->delete('/v2/software/soft-aaaaaaaa')->assertResponseStatus(401);

        $this->be((new Consumer(0, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));
        $this->delete('/v2/software/soft-aaaaaaaa')
            ->notSeeInDatabase(
                'software',
                [
                    'id' => 'soft-aaaaaaaa',
                    'deleted_at' => null,
                ],
                'ecloud'
            )->assertResponseStatus(204);
    }
}
