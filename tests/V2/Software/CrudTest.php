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
        $this->get('/v2/software')
            ->seeJson([
                'id' => 'soft-test',
                'name' => 'Test Software',
                'platform' => 'Linux',
                'visibility' => 'public',
            ])
            ->assertResponseStatus(200);

        $software = Software::first();
        $software->setAttribute('visibility', Software::VISIBILITY_PRIVATE)->save();

        $this->get('/v2/software')
            ->dontSeeJson([
                'id' => 'soft-test',
                'name' => 'Test Software',
                'platform' => 'Linux',
                'visibility' => 'private',
        ])
            ->assertResponseStatus(200);

        $this->be((new Consumer(0, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));
        $this->get('/v2/software/soft-test')
            ->seeJson([
                'id' => 'soft-test',
                'name' => 'Test Software',
                'platform' => 'Linux',
                'visibility' => 'private',
            ])
            ->assertResponseStatus(200);
    }

    public function testShow()
    {
        $this->get('/v2/software/soft-test')
            ->seeJson([
                'id' => 'soft-test',
                'name' => 'Test Software',
                'platform' => 'Linux',
                'visibility' => 'public',
            ])
            ->assertResponseStatus(200);

        $software = Software::first();
        $software->setAttribute('visibility', Software::VISIBILITY_PRIVATE)->save();
        $this->get('/v2/software/soft-test')->assertResponseStatus(404);

        $this->be((new Consumer(0, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));
        $this->get('/v2/software/soft-test')->assertResponseStatus(200);
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
        $this->patch('/v2/software/soft-test', $data)->assertResponseStatus(401);

        $this->be((new Consumer(0, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));
        $this->patch('/v2/software/soft-test', $data)
            ->seeInDatabase('software', $data, 'ecloud')
            ->assertResponseStatus(200);
    }

    public function testDestroy()
    {
        // Not admin fails
        $this->delete('/v2/software/soft-test')->assertResponseStatus(401);

        $this->be((new Consumer(0, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));
        $this->delete('/v2/software/soft-test')
            ->notSeeInDatabase(
                'software',
                [
                    'id' => 'soft-test',
                    'deleted_at' => null,
                ],
                'ecloud'
            )->assertResponseStatus(204);
    }
}
